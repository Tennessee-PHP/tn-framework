<?php

namespace TN\TN_Billing\Model\Apple;

use Carbon\Carbon;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\Validator;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Apple\Transaction;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

/**
 * an apple receipt
 * 
 */
class Receipt
{
    use \TN\TN_Core\Trait\Getter;

    /** @var string apple's ID */
    protected string $appBundleId;

    /** @var string the receipt data - just in case we ever want to do something with this in the future! */
    protected string $receiptData;

    /** @var User the user who this receipt belongs to */
    protected User $user;

    /** @var ResponseInterface the response from validation */
    protected ResponseInterface $response;

    /**
     * constructor
     * @param string $receiptData
     * @param string $appBundleId
     * @param User $user
     */
    private function __construct(string $receiptData, string $appBundleId, User $user)
    {
        $this->receiptData = $receiptData;
        $this->appBundleId = $appBundleId;
        $this->user = $user;
    }

    /**
     * factory method
     * @param string $receiptData
     * @param string $appBundleId
     * @param User $user
     * @return Receipt
     */
    public static function getFromData(string $receiptData, string $appBundleId, User $user): Receipt
    {
        return new self($receiptData, $appBundleId, $user);
    }

    /**
     * make a response
     * @param string $endpoint
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ReceiptValidator\RunTimeException
     */
    private function setResponse(string $endpoint): void
    {
        $validator = new Validator($endpoint); // Or iTunesValidator::ENDPOINT_SANDBOX if sandbox testing
        $this->response = $validator
            ->setSharedSecret($_ENV['IOS_APPSTORE_SECRET'])
            ->setReceiptData($this->receiptData)
            ->validate(); // use setSharedSecret() if for recurring subscriptions
    }

    /** @return array get the data to send back to the client (app) */
    public function getResponseToClient(): array
    {
        if ($this->response->isValid()) {
            $collection = [];
            $seenNonExpired = false;
            foreach ($this->getPurchasesSorted(SORT_DESC) as $purchase) {
                $endDate = $purchase->getExpiresDate();
                $cancelDate = $purchase->getCancellationDate();
                if ($cancelDate instanceof Carbon && $cancelDate->lessThan($endDate)) {
                    $endDate = $cancelDate;
                }

                $isExpired = !$endDate->isFuture() || $seenNonExpired;
                if (!$isExpired) {
                    $seenNonExpired = true;
                }

                $collection[] = [
                    'id' => $purchase->getProductId(),
                    'purchaseDate' => $purchase->getPurchaseDate()->getPreciseTimestamp(3),
                    'expiryDate' => $endDate->getPreciseTimestamp(3),
                    'isExpired' => $isExpired
                ];
            }
            $collection = array_reverse($collection);
            return [
                'ok' => true,
                'data' => [
                    'ineligible_for_intro_price' => [],
                    'collection' => $collection
                ]
            ];
        }
        return [
            'ok' => false,
            'code' => $this->response->getResultCode(), // whatever we just saw
            'message' => $this->response->getResultCode()
        ];
    }

    /**
     * make the original request and return if it's valid
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ReceiptValidator\RunTimeException
     */
    public function isValid(): bool
    {
        $this->setResponse(Validator::ENDPOINT_PRODUCTION);

        // response code 21007 means it was a sandbox receipt that was sent to production -
        // so send it again to sandbox
        if (!$this->response->isValid() && $this->response->getResultCode() == 21007) {
            $this->setResponse(Validator::ENDPOINT_SANDBOX);
        }

        return $this->response->isValid();
    }

    private function calculateAmount(Plan $plan, BillingCycle $billingCycle): float
    {
        $price = $plan->getPrice($billingCycle);
        return (int)$price->price + 0.99;
    }

    public function getPurchasesSorted($sort = SORT_ASC): array
    {
        // apple doesn't guarantee they're sorted so let's do that first
        $purchases = $this->response->getPurchases();
        $startTimestamps = [];
        foreach ($purchases as $purchase) {
            $startTimestamps[] = $purchase->getPurchaseDate()->getTimestamp();
        }
        array_multisort($startTimestamps, $sort, $purchases);
        return $purchases;
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function createTransactionsAndSubscriptions(): void
    {
        // iterate through the products
        foreach ($this->getPurchasesSorted() as $purchase) {
            $appleId = $purchase->getTransactionId();
            $existingTx = Transaction::readFromAppleId($appleId);
            if ($existingTx instanceof Transaction) {
                // we already saved a subscription and transaction for this one - we're good
                if ($existingTx->userId !== $this->user->id) {
                    $existingTx->switchToUser($this->user);
                }
                continue;
            }

            $startTs = $purchase->getPurchaseDate()->getTimestamp();
            $endTs = $purchase->getExpiresDate()->getTimestamp();

            // first create the subscription
            if (str_contains($purchase->getProductId(), '_')) {
                $parts = explode('_', $purchase->getProductId());
                $plan = Plan::getInstanceByKey($parts[0]);
                $billingCycle = BillingCycle::getInstanceByKey($parts[1]);
            } else {
                $parts = explode('.', $purchase->getProductId());
                $plan = Plan::getInstanceByKey($parts[1]);
                $billingCycle = BillingCycle::getInstanceByKey($parts[0]);
            }
            if (!($plan instanceof Plan)) {
                throw new ValidationException('Plan could not be found for plan key from product id: ' . $parts[0]);
            }
            if (!($billingCycle instanceof BillingCycle)) {
                throw new ValidationException('Billing cycle could not be found for billign cycle key from product id: ' . $parts[1]);
            }

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

            // now create the transaction
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
                'receiptData' => $this->receiptData,
                'success' => true
            ]);

            if ($startTs > $subscription->lastTransactionTs) {
                $update = [];
                $update['lastTransactionTs'] = $startTs;
                $update['lastTransactionAmount'] = $amount;
                $subscription->update($update);
            }

        }

        $this->user->subscriptionsChanged();
    }

}