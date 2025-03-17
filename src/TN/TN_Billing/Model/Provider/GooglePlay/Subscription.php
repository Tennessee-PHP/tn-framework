<?php

namespace TN\TN_Billing\Model\Provider\GooglePlay;

use Google\Service\AndroidPublisher;
use ReceiptValidator\GooglePlay\Acknowledger;
use ReceiptValidator\GooglePlay\SubscriptionResponse;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription as TNSubscription;
use TN\TN_Billing\Model\Transaction\GooglePlay\Transaction;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

/**
 * when cron runs a job, log the results
 *
 */
#[TableName('googleplay_subscriptions')]
class Subscription implements Persistence
{
    use MySQL;
    use PersistentModel;

    public int $userId = 0;
    public int $tnSubscriptionId = 0;
    public string $productId = '';
    public string $purchaseToken = '';
    public string $packageName = '';
    public string $errors = '';

    /**
     * get one of these from a purchase token
     * @param string $purchaseToken
     * @return Subscription
     * @throws ValidationException
     */
    public static function getFromPurchaseToken(string $purchaseToken): Subscription
    {
        // let's try to read one
        $matches = self::searchByProperty('purchaseToken', $purchaseToken);
        if (count($matches)) {
            return $matches[0];
        }

        // insert a new one
        $subscription = static::getInstance();
        $subscription->update([
            'purchaseToken' => $purchaseToken
        ]);
        return $subscription;
    }

    /**
     * get an android publisher to make API requests with
     * @return AndroidPublisher
     * @throws \Google\Exception
     */
    protected function getPublisher(): AndroidPublisher
    {
        $googleClient = new \Google_Client();
        $googleClient->setScopes([\Google\Service\AndroidPublisher::ANDROIDPUBLISHER]);
        $googleClient->setApplicationName('Tennessee PHP');
        $googleClient->setAuthConfig($_ENV['TN_ROOT'] . 'googleplay.json');
        return new \Google\Service\AndroidPublisher($googleClient);
    }

    /**
     * this should tell google play we've given them the goods and they don't need to auto-refund after 3 days
     * @param string $developerPayload
     * @return bool
     * @throws ValidationException
     */
    public function acknowledge(string $developerPayload): bool
    {
        try {
            $acknowledgement = new Acknowledger(
                $this->getPublisher(),
                $this->packageName,
                $this->productId,
                $this->purchaseToken,
                Acknowledger::ACKNOWLEDGE_STRATEGY_IMPLICIT
            );
            $acknowledgement->acknowledge(Acknowledger::SUBSCRIPTION, $developerPayload);
        } catch (\Exception $e) {
            $this->update([
                'errors' => Time::getNow() . ': acknowledge error: ' . $this->errors . $e->getMessage() . PHP_EOL
            ]);
            return false;
        }
        return true;
    }

    /**
     * gets the response from google play when querying their API for the subscription's details
     * @return SubscriptionResponse
     * @throws ValidationException
     */
    protected function getGooglePlayResponse(): SubscriptionResponse
    {
        try {
            $validator = new \ReceiptValidator\GooglePlay\Validator($this->getPublisher());
            return $validator->setPackageName($this->packageName)
                ->setProductId($this->productId)
                ->setPurchaseToken($this->purchaseToken)
                ->validateSubscription();
        } catch (\Exception $e) {
            $this->update([
                'errors' => Time::getNow() . ': getGooglePlayResponse error: ' . $this->errors . $e->getMessage() . PHP_EOL
            ]);
            throw new ValidationException($e->getMessage());
        }
    }

    /**
     * add a new transaction in
     * @param TNSubscription $tnSub
     * @param SubscriptionResponse $response
     * @return void
     * @throws ValidationException
     */
    protected function addTransaction(TNSubscription $tnSub, SubscriptionResponse $response): void
    {
        // use price if in USD, else get from pricing DB
        if (strtolower($response->getPriceCurrencyCode()) === 'USD') {
            $amount = $response->getPriceAmountMicros() / 1000000;
        } else {
            $plan = $this->getPlan();
            $billingCycle = $this->getBillingCycle();
            $amount = $plan->getPrice($billingCycle)->price;
        }
        $transaction = Transaction::getInstance();
        $res = $transaction->update([
            'userId' => $this->userId,
            'amount' => $amount,
            'ts' => Time::getNow(),
            'voucherCodeId' => 0,
            'discount' => 0,
            'subscriptionId' => $tnSub->id,
            'packageName' => $this->packageName,
            'productId' => $this->productId,
            'googlePlaySubscriptionId' => $this->id,
            'success' => true
        ]);
        if (is_array($res)) {
            $this->update([
                'errors' => Time::getNow() . ': addTransaction error: ' . print_r($res, true) . PHP_EOL
            ]);
        }

        $tnSub->update([
            'lastTransactionTs' => Time::getNow(),
            'lastTransactionAmount' => $amount,
            'numTransactions' => $tnSub->numTransactions + 1
        ]);
    }

    /** @return Plan|false returns a plan from the productId */
    protected function getPlan(): mixed
    {
        $parts = explode('.', $this->productId);
        return Plan::getInstanceByKey($parts[1]);
    }

    /**
     * returns a billing cycle from the productId
     * @return BillingCycle|false
     */
    protected function getBillingCycle(): mixed
    {
        $parts = explode('.', $this->productId);
        return BillingCycle::getInstanceByKey($parts[0]);
    }

    /**
     * updates the subscription if we can, with the associated user
     * @return void
     * @throws ValidationException
     */
    public function updateTnSubscription(): void
    {
        // we need a subscription response and a user ID
        $userId = (int)($_GET['userId'] ?? $this->userId);
        if ($userId === 0) {
            $this->update([
                'errors' => Time::getNow() . ': updateTnSubscription error: no userId' . PHP_EOL
            ]);
            return;
        }

        if ($this->productId === '' || $this->packageName === '') {
            $this->update([
                'errors' => Time::getNow() . ': updateTnSubscription error: no productId/packageName' . PHP_EOL
            ]);
            return;
        }

        $response = $this->getGooglePlayResponse();

        // ok; so do we have a subscription already?
        $tnSub = $this->tnSubscriptionId > 0 ? TNSubscription::readFromId($this->tnSubscriptionId) : null;

        // yes? if the new end date is greater than the existing one, let's add a new transaction and extend it
        if ($tnSub instanceof TNSubscription) {
            $newEndTs = round($response->getExpiryTimeMillis() / 1000);
            if ($newEndTs > $tnSub->endTs) {
                $tnSub->update([
                    'endTs' => $newEndTs
                ]);
                $this->addTransaction($tnSub, $response);
            }

            // if the subscription has ended, can we figure out how to apply that as well to the end reason?!
            $cancelReason = $response->getCancelReason();
            if ($cancelReason === 0 && $response->getUserCancellationTimeMillis()) {
                $tnSub->update(['endReason' => 'user-cancelled']);
            } else if ($cancelReason === 1) {
                $tnSub->update(['endReason' => 'payment-failed']);
            } else if ($cancelReason === 2) {
                $tnSub->update(['endReason' => 'upgraded']);
            }
        } else {
            // no? ok then. so create a new one with a single transaction.
            $tnSub = TNSubscription::getInstance();
            $tnSub->update([
                'active' => 1,
                'userId' => $userId, // next thing to try is to change this to $_GET['userId'] if it's set
                'planKey' => $this->getPlan()->key,
                'billingCycleKey' => $this->getBillingCycle()->key,
                'gatewayKey' => 'googleplay',
                'voucherCodeId' => 0,
                'startTs' => round($response->getStartTimeMillis() / 1000),
                'endTs' => round($response->getExpiryTimeMillis() / 1000),
                'nextTransactionTs' => 0
            ]);
            $this->update([
                'tnSubscriptionId' => $tnSub->id
            ]);
            $this->addTransaction($tnSub, $response);
        }

        $user = User::readFromId($this->userId);
        if ($user instanceof User) {
            $user->subscriptionsChanged();
        }

        // let's acknowledge it
        $this->acknowledge($response->getDeveloperPayload());

    }


}