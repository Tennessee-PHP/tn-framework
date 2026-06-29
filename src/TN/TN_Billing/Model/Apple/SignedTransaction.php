<?php

namespace TN\TN_Billing\Model\Apple;

use AppStoreServerLibrary\Models\Environment;
use AppStoreServerLibrary\Models\JWSTransactionDecodedPayload;
use AppStoreServerLibrary\SignedDataVerifier;
use AppStoreServerLibrary\SignedDataVerifier\VerificationException;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Apple\Transaction;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;
use ValueError;

/**
 * a verified StoreKit 2 signed transaction from Apple
 */
class SignedTransaction
{
    use \TN\TN_Core\Trait\Getter;

    protected string $jws;
    protected string $appBundleId;
    protected User $user;
    protected ?JWSTransactionDecodedPayload $payload = null;

    /** @var callable|null */
    private static $verifierForTests = null;

    private function __construct(string $jws, string $appBundleId, User $user)
    {
        $this->jws = $jws;
        $this->appBundleId = $appBundleId;
        $this->user = $user;
    }

    public static function getFromJws(string $jws, string $appBundleId, User $user): SignedTransaction
    {
        return new self($jws, $appBundleId, $user);
    }

    public static function setVerifierForTests(?callable $verifier): void
    {
        self::$verifierForTests = $verifier;
    }

    public static function looksLikeJws(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', trim($value)) === 1;
    }

    public static function resolveSignedPayload(object $request): string
    {
        foreach (['jwsRepresentation', 'signedTransaction', 'receipt'] as $field) {
            $value = trim((string)($request->{$field} ?? ''));
            if ($value !== '' && self::looksLikeJws($value)) {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array{string, string}
     */
    public static function parseProductId(string $productId): array
    {
        if (str_contains($productId, '_')) {
            $parts = explode('_', $productId, 2);
            return [$parts[0] ?? '', $parts[1] ?? ''];
        }

        $parts = explode('.', $productId, 2);
        return [$parts[1] ?? '', $parts[0] ?? ''];
    }

    /**
     * @throws ValidationException
     */
    public function verify(): bool
    {
        if (self::$verifierForTests !== null) {
            $payload = (self::$verifierForTests)($this->jws, $this->appBundleId, $this->getAppAppleId());
            if (!($payload instanceof JWSTransactionDecodedPayload)) {
                throw new ValidationException('StoreKit transaction verifier returned an invalid payload.');
            }
            $this->setVerifiedPayload($payload);
            return true;
        }

        $lastException = null;
        $appAppleId = $this->getAppAppleId();
        if ($appAppleId !== null) {
            try {
                $this->setVerifiedPayload($this->verifyForEnvironment(Environment::PRODUCTION, $appAppleId));
                return true;
            } catch (VerificationException|ValueError $e) {
                $lastException = $e;
            }
        }

        try {
            $this->setVerifiedPayload($this->verifyForEnvironment(Environment::SANDBOX, null));
            return true;
        } catch (VerificationException|ValueError $e) {
            $lastException = $e;
        }

        throw new ValidationException('StoreKit transaction verification failed: ' . $lastException->getMessage());
    }

    /**
     * @throws ValidationException
     */
    public function validateProductId(string $productId): void
    {
        $payloadProductId = $this->getVerifiedPayload()->getProductId();
        if ($payloadProductId !== $productId) {
            throw new ValidationException('Product ID did not match signed transaction.');
        }
    }

    /**
     * @throws ValidationException
     */
    public function createTransactionsAndSubscriptions(): void
    {
        $payload = $this->getVerifiedPayload();
        $appleId = $this->requireString($payload->getTransactionId(), 'transactionId');
        $existingTx = Transaction::readFromAppleId($appleId);
        if ($existingTx instanceof Transaction) {
            if ($existingTx->userId !== $this->user->id) {
                $existingTx->switchToUser($this->user);
            }
            $this->user->subscriptionsChanged();
            return;
        }

        $startTs = $this->timestampFromMilliseconds($payload->getPurchaseDate(), 'purchaseDate');
        $endTs = $this->timestampFromMilliseconds($payload->getExpiresDate(), 'expiresDate');
        [$plan, $billingCycle] = $this->getPlanAndBillingCycle($this->requireString($payload->getProductId(), 'productId'));

        $subscription = Subscription::getExtendableUserSubscriptionByGateway(
            $this->user,
            'apple',
            $plan->key,
            $billingCycle->key,
            $startTs
        );

        $update = [
            'userId' => $this->user->id,
            'planKey' => $plan->key,
            'billingCycleKey' => $billingCycle->key,
            'gatewayKey' => 'apple',
            'voucherCodeId' => 0,
            'startTs' => $startTs,
            'endTs' => $endTs,
            'nextTransactionTs' => 0
        ];

        if ($subscription instanceof Subscription) {
            $update['startTs'] = min($startTs, $subscription->startTs);
            $update['endTs'] = max($endTs, $subscription->endTs);
            $update['numTransactions'] = $subscription->numTransactions + 1;
        } else {
            $subscription = Subscription::getInstance();
            $update['active'] = true;
            $update['numTransactions'] = 1;
        }

        $subscription->update($update);

        $amount = $this->calculateAmount($plan, $billingCycle);
        $transaction = Transaction::getInstance();
        $transaction->update([
            'userId' => $this->user->id,
            'amount' => $amount,
            'ts' => $startTs,
            'voucherCodeId' => 0,
            'discount' => 0,
            'subscriptionId' => $subscription->id,
            'appleId' => $appleId,
            'appBundleId' => $this->appBundleId,
            'receiptData' => $this->jws,
            'success' => true
        ]);

        if ($startTs > $subscription->lastTransactionTs) {
            $subscription->update([
                'lastTransactionTs' => $startTs,
                'lastTransactionAmount' => $amount
            ]);
        }

        $this->user->subscriptionsChanged();
    }

    /**
     * @throws ValidationException
     */
    public function getVerifiedPayload(): JWSTransactionDecodedPayload
    {
        if (!($this->payload instanceof JWSTransactionDecodedPayload)) {
            $this->verify();
        }

        return $this->payload;
    }

    /**
     * @throws VerificationException
     */
    private function verifyForEnvironment(Environment $environment, ?int $appAppleId): JWSTransactionDecodedPayload
    {
        $verifier = new SignedDataVerifier(
            rootCertificates: self::getRootCertificates(),
            enableOnlineChecks: true,
            environment: $environment,
            bundleId: $this->appBundleId,
            appAppleId: $appAppleId
        );

        return $verifier->verifyAndDecodeSignedTransaction($this->jws);
    }

    /**
     * @throws ValidationException
     */
    private function setVerifiedPayload(JWSTransactionDecodedPayload $payload): void
    {
        if ($payload->getBundleId() !== $this->appBundleId) {
            throw new ValidationException('Bundle ID did not match signed transaction.');
        }

        $this->payload = $payload;
    }

    /**
     * @return string[]
     * @throws ValidationException
     */
    private static function getRootCertificates(): array
    {
        $root = dirname(__DIR__, 5) . '/resources/apple';
        $certificates = [];
        foreach (['AppleRootCA-G3.cer', 'AppleRootCA-G2.cer'] as $filename) {
            $contents = file_get_contents($root . '/' . $filename);
            if ($contents === false) {
                throw new ValidationException('Apple root certificate could not be loaded: ' . $filename);
            }
            $certificates[] = $contents;
        }

        return $certificates;
    }

    private function getAppAppleId(): ?int
    {
        $appAppleId = trim((string)($_ENV['IOS_APP_APPLE_ID'] ?? ''));
        return $appAppleId === '' ? null : (int)$appAppleId;
    }

    /**
     * @return array{Plan, BillingCycle}
     * @throws ValidationException
     */
    public function getPlanAndBillingCycle(string $productId): array
    {
        [$planKey, $billingCycleKey] = self::parseProductId($productId);

        $plan = Plan::getInstanceByKey($planKey);
        if (!($plan instanceof Plan)) {
            throw new ValidationException('Plan could not be found for plan key from product id: ' . $planKey);
        }

        $billingCycle = BillingCycle::getInstanceByKey($billingCycleKey);
        if (!($billingCycle instanceof BillingCycle)) {
            throw new ValidationException('Billing cycle could not be found for billing cycle key from product id: ' . $billingCycleKey);
        }

        return [$plan, $billingCycle];
    }

    private function calculateAmount(Plan $plan, BillingCycle $billingCycle): float
    {
        $price = $plan->getPrice($billingCycle);
        return (int)$price->price + 0.99;
    }

    /**
     * @throws ValidationException
     */
    private function requireString(?string $value, string $field): string
    {
        if ($value === null || $value === '') {
            throw new ValidationException('StoreKit transaction payload is missing ' . $field . '.');
        }

        return $value;
    }

    /**
     * @throws ValidationException
     */
    private function timestampFromMilliseconds(?int $value, string $field): int
    {
        if ($value === null || $value <= 0) {
            throw new ValidationException('StoreKit transaction payload is missing ' . $field . '.');
        }

        return (int)floor($value / 1000);
    }
}
