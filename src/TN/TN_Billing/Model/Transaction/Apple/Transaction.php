<?php

namespace TN\TN_Billing\Model\Transaction\Apple;

use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User;

/**
 * an apple transaction
 * 
 */
#[TableName('apple_transactions')]
class Transaction extends \TN\TN_Billing\Model\Transaction\Transaction
{
    use MySQL;

    /** @var string apple's ID */
    public string $appleId;

    /** @var string the app that the purchase originated in */
    public string $appBundleId;

    /** @var string the receipt data - just in case we ever want to do something with this in the future! */
    public string $receiptData;

    /**
     * search for a transaction from the apple ID
     * @param string $appleId
     * @return Transaction|null
     */
    public static function readFromAppleId(string $appleId): ?Transaction
    {
        $transactions = self::searchByProperty('appleId', $appleId);
        return count($transactions) > 0 ? $transactions[0] : null;
    }

    /**
     * gets all transactions of this apple type
     * @param User $user
     * @return array
     */
    public static function getFromUser(User $user): array
    {
        return self::searchByProperty('userId', $user->id);
    }

    /**
     * get all transactions for a given subscription
     * @param Subscription $subscription
     * @return array
     */
    public static function getFromSubscription(Subscription $subscription): array
    {
        return self::searchByProperty('subscriptionId', $subscription->id);
    }

    /**
     * apple have told us this refunded
     * @param int $ts
     * @param string $reason
     * @return Refund
     * @throws ValidationException
     */
    public function refunded(int $ts, string $reason = ''): Refund
    {
        $refund = Refund::getInstance();
        $refund->update([
            'ts' => $ts,
            'userId' => $this->userId,
            'transactionId' => $this->id,
            'transactionClass' => __CLASS__,
            'amount' => $this->amount,
            'reason' => $reason,
            'comment' => 'received by notification from apple'
        ]);

        $this->update([
            'refunded' => true
        ]);

        return $refund;
    }

    /**
     * @inheritDoc
     */
    public function getFee(): float
    {
        return ($this->amount * 0.15); // 15% for apple app store as per small business agreement with them
    }

}