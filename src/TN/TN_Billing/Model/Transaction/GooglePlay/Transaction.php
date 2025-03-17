<?php

namespace TN\TN_Billing\Model\Transaction\GooglePlay;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User;

/**
 * an apple transaction
 *
 */
#[TableName('googleplay_transactions')]
class Transaction extends \TN\TN_Billing\Model\Transaction\Transaction
{
    use MySQL;

    /** @var string the app that the purchase originated in */
    public string $packageName = '';

    /** @var string the product ID this is for */
    public string $productId = '';

    /** @var int google play subscription id */
    public int $googlePlaySubscriptionId = 0;

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
     * @inheritDoc
     */
    public function getFee(): float
    {
        return ($this->amount * 0.15); // 15% for google play
    }

}