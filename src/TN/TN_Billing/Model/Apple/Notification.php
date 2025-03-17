<?php

namespace TN\TN_Billing\Model\Apple;

use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Apple\Transaction;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

/**
 * represents server to server v2 push from app store
 *
 */
class Notification
{
    use \TN\TN_Core\Trait\Getter;

    /** @var string */
    protected string $notificationType;

    /** @var string */
    protected string $subType;

    /** @var string */
    protected string $bundleId;

    /** @var string */
    protected string $environment;

    /** @var object */
    protected object $transactionInfo;

    /** @var object */
    protected object $renewalInfo;

    /**
     * decodes from jwt
     * @param string $signedPayload
     * @return array
     */
    protected static function decodePayload(string $signedPayload): object
    {
        list($header, $payload, $signature) = explode('.', $signedPayload);
        return json_decode(base64_decode($payload));
    }

    /**
     * factory method from string
     * @param string $signedPayload
     * @return Notification
     */
    public static function getFromSignedPayload(string $signedPayload): Notification
    {
        $data = self::decodePayload($signedPayload);
        return new Notification($data);
    }

    /** @param object $data constructor */
    protected function __construct(object $data) {
        $this->notificationType = strtoupper($data->notificationType);
        $this->subType = strtoupper($data->subtype);
        $data = $data->data;
        $this->bundleId = $data->bundleId;
        $this->environment = strtoupper($data->environment);
        $this->transactionInfo = self::decodePayload($data->signedTransactionInfo);
        $this->renewalInfo = self::decodePayload($data->signedRenewalInfo);
    }

    /**
     * @return Subscription
     * @throws ValidationException
     */
    protected function getSubscription(): Subscription
    {
        // get the apple transaction from original id- throw if not found
        $transaction = Transaction::readFromAppleId($this->transactionInfo->originalTransactionId);
        if (!$transaction) {
            throw new ValidationException('Could not find transaction with apple id ' . $this->transactionInfo->originalTransactionId);
        }

        // now get the subscription reading the subscription id from that transaction id- throw if not found
        $subscription = Subscription::readFromId($transaction->subscriptionId);
        if (!$subscription) {
            throw new ValidationException('Could not find subscription with id ' . $transaction->subscriptionId);
        }

        return $subscription;
    }

    private function calculateAmount(Plan $plan, BillingCycle $billingCycle): float
    {
        $price = $plan->getPrice($billingCycle);
        return (int)$price->price + 0.99;
    }

    /**
     * apply the notification to our own database
     * @return void
     * @throws ValidationException
     */
    public function apply(): void
    {
        if ($this->environment !== 'PRODUCTION') {
            return;
        }

        switch ($this->notificationType) {
            case 'DID_CHANGE_RENEWAL_STATUS':
                $this->applyAutoRenewEnabled();
                return;
            case 'DID_FAIL_TO_RENEW':
                $this->applyAutoRenewFailed();
                return;
            case 'DID_RENEW':
            case 'DID_RENEW-':
                $this->applyAutoRenewSucceeded();
                return;
            case 'REFUND':
                $this->applyRefund();
            case 'SUBSCRIBED':
                if ($this->subType === 'RESUBSCRIBE') {
                    $this->applyAutoRenewSucceeded();
                }
            // do something with subType === 'INITIAL_BUY'?
            default:
                return;
        }
    }

    /**
     * the user turned off (or on) auto renew
     * @return void
     * @throws ValidationException
     */
    protected function applyAutoRenewEnabled(): void
    {
        $enabled = $this->subType === 'AUTO_RENEW_ENABLED';
        $subscription = $this->getSubscription();
        $subscription->update([
            'endReason' => $enabled ? '' : 'user-cancelled'
        ]);
        $user = User::readFromId($subscription->userId);
        if ($user instanceof User) {
            $user->subscriptionsChanged();
        }
    }

    /**
     * apple failed to collect payment on the auto renew
     * @return void
     * @throws ValidationException
     */
    protected function applyAutoRenewFailed(): void
    {
        $inGracePeriod = $this->subType === 'GRACE_PERIOD';
        $subscription = $this->getSubscription();
        $subscription->update([
            'endReason' => 'payment-failed'
        ]);
        $user = User::readFromId($subscription->userId);
        if ($user instanceof User) {
            $user->subscriptionsChanged();
        }
    }

    /**
     * apple succeeded in collecting payment for auto renew
     * @return void
     * @throws ValidationException
     */
    protected function applyAutoRenewSucceeded(): void
    {
        $billingRecovery = $this->subType === 'BILLING_RECOVERY';
        $subscription = $this->getSubscription();

        // check if we already have this transaction: if so, do nothing
        $transaction = Transaction::readFromAppleId($this->transactionInfo->transactionId);
        if ($transaction instanceof Transaction) {
            throw new ValidationException('Renew notification, but transaction already exists in our DB with apple ID: ' . $this->transactionInfo->transactionId);
        }

        // we may need to change the billing cycle and/or plan on the subscription based on the product
        $productId = $this->transactionInfo->productId;
        if (str_contains($productId, '_')) {
            $parts = explode('_', $productId);
            $plan = Plan::getInstanceByKey($parts[0]);
            $billingCycle = BillingCycle::getInstanceByKey($parts[1]);
        } else {
            $parts = explode('.', $productId);
            $plan = Plan::getInstanceByKey($parts[1]);
            $billingCycle = BillingCycle::getInstanceByKey($parts[0]);
        }
        if ($plan instanceof Plan && $subscription->planKey !== $plan->key) {
            $subscription->update([
                'planKey' => $plan->key
            ]);
        }
        if ($billingCycle instanceof BillingCycle && $subscription->billingCycleKey !== $billingCycle->key) {
            $subscription->update([
                'billingCycleKey' => $billingCycle->key
            ]);
        }

        // create a new transaction
        $amount = $this->calculateAmount($subscription->getPlan(), $subscription->getBillingCycle());
        $transaction = Transaction::getInstance();
        $transaction->update([
            'userId' => $subscription->userId,
            'amount' => $amount,
            'ts' => (int)($this->transactionInfo->purchaseDate / 1000),
            'voucherCodeId' => 0,
            'discount' => 0,
            'subscriptionId' => $subscription->id,
            'appleId' => $this->transactionInfo->transactionId,
            'appBundleId' => $this->bundleId,
            'receiptData' => '',
            'success' => true
        ]);

        // edit the dates on the subscription
        $subscription->update([
            'endReason' => '',
            'endTs' => (int)($this->transactionInfo->expiresDate / 1000),
            'numTransactions' => $subscription->numTransactions + 1,
            'lastTransactionTs' => (int)($this->transactionInfo->purchaseDate / 1000),
            'lastTransactionAmount' => $amount
        ]);

        $user = User::readFromId($subscription->userId);
        if ($user instanceof User) {
            $user->subscriptionsChanged();
        }
    }

    /**
     * a payment was refunded by apple
     * @return void
     * @throws ValidationException
     */
    protected function applyRefund(): void
    {
        $transaction = Transaction::readFromAppleId($this->transactionInfo->transactionId);
        if (!$transaction) {
            throw new ValidationException('Could not find transaction to refund: ' . $this->transactionInfo->transactionId);
        }

        $transaction->refunded((int)($this->transactionInfo->revocationDate / 1000),
            $this->transactionInfo->revocationReason == 1 ? 'issue' : 'incorrect');

        $user = User::readFromId($transaction->userId);
        if ($user instanceof User) {
            $user->subscriptionsChanged();
        }

    }
}