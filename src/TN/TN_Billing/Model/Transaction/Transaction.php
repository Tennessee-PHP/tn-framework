<?php

namespace TN\TN_Billing\Model\Transaction;

use JetBrains\PhpStorm\ArrayShape;
use PDO;
use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\CountAndTotalResult;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Trait\GetUser;

/**
 * an abstract transaction - change of money between this website and a customer each gateway is slightly different so
 * separate transaction classes for each, each of which subclass this transaction class
 *
 *
 */
abstract class Transaction implements Persistence
{
    use PersistentModel;
    use GetUser;

    /** @var int timestamp of execution */
    // todo: refactor to datetime
    public int $ts = 0;

    /** @var int user's id */
    public int $userId;

    /** @var int id of an associated subscription, if applicable */
    public int $subscriptionId = 0;

    /** @var int id of an associated gift subscription */
    public int $giftSubscriptionId = 0;

    /** @var int id of voucher code used */
    public int $voucherCodeId = 0;

    /** @var bool successful? */
    public bool $success = false;

    /** @var string error message to show the user */
    public string $errorMsg = '';

    /** @var bool refunded? */
    public bool $refunded = false;

    /** @var float the amount of $ */
    public float $amount;

    /** @var float the discounted $ (ALREADY discounted off $amount!!) */
    public float $discount = 0;

    /** @return mixed */
    public function getProduct(): mixed
    {
        return $this->subscriptionId > 0 ? Subscription::readFromId($this->subscriptionId) : ($this->giftSubscriptionId > 0 ? GiftSubscription::readFromId($this->giftSubscriptionId) :
            null);
    }

    /** @return bool is this the latest transaction in the subscription? */
    public function isLatestTransactionInSubscription(): bool
    {
        if ($this->subscriptionId === 0) {
            return false;
        }
        $subscription = Subscription::readFromId($this->subscriptionId);
        if (!($subscription instanceof Subscription)) {
            return false;
        }
        $allTransactions = self::getAllFromSubscription($subscription);
        return (count($allTransactions) > 0 && $allTransactions[0]->id === $this->id);
    }

    /**
     * this happens when a purchase is associated with a user defined outside of this website; that user is
     * then linked to a different user in this website. E.g. app store purchase logs out of this website's user system
     * and back in as a different user.
     * @param User $user
     * @return void
     * @throws ValidationException
     */
    public function switchToUser(User $user): void
    {
        $subscription = Subscription::readFromId($this->subscriptionId);
        $subscription->update([
            'userId' => $user->id
        ]);
        foreach (self::getAllFromSubscription($subscription) as $transaction) {
            $transaction->update([
                'userId' => $user->id
            ]);
        }
    }

    /**
     * what was the fee for this transaction?
     * @return float
     */
    abstract public function getFee(): float;

    /**
     * gets all the transactions of all t subclasses for a user
     * @param User $user
     * @return array
     */
    abstract public static function getFromUser(User $user): array;

    /**
     * gets all the transactions of all tx subclasses for a subscription
     * @param Subscription $subscription
     * @return array
     */
    abstract public static function getFromSubscription(Subscription $subscription): array;

    /**
     * @param int $startTs
     * @param int $endTs
     * @param array $filters
     * @return array
     */
    // todo refactor to new search/count
    protected static function buildSearchQuery(int $startTs, int $endTs, array $filters): array
    {
        $tables = [get_called_class()::getTableName() . " as t"];
        $params = [];

        $conditions = [];
        $conditions[] = "`success` = 1";
        $conditions[] = "`ts` >= ?";
        $params[] = $startTs;
        $conditions[] = "`ts` <= ?";
        $params[] = $endTs;

        $subscriptionFilters = [];
        $otherFilters = [];

        // split filters into subscription/other filters
        foreach ($filters as $filter => $value) {
            if (in_array($filter, ['planKey', 'billingCycleKey'])) {
                $subscriptionFilters[$filter] = $value;
            } else {
                $otherFilters[$filter] = $value;
            }
        }

        if (!empty($subscriptionFilters)) {
            $tables[] = Subscription::getTableName() . " as s";
            $conditions[] = "t.`subscriptionId` = `s`.`id`";

            unset($value);
            unset($filter);
            foreach ($subscriptionFilters as $filter => $value) {
                $conditions[] = "s.`$filter` = ?";
                $params[] = $value;
            }
        }

        unset($value);
        unset($filter);
        foreach ($otherFilters as $filter => $value) {
            if ($filter === 'recurring') {
                if ($value) {
                    $conditions[] = "t.`subscriptionId` > 0";
                } else {
                    $conditions[] = "t.`subscriptionId` = 0";
                }
            }
            if ($filter === 'productTypeKey') {
                if ($value === 'subscription') {
                    $conditions[] = "t.`subscriptionId` > 0";
                } elseif ($value === 'giftSubscription') {
                    $conditions[] = "t.`giftSubscriptionId` > 0";
                }
            }
        }

        return [
            'tables' => $tables,
            'conditions' => $conditions,
            'params' => $params
        ];
    }

    /**
     * count successful transactions between times
     * @param int $startTs
     * @param int $endTs
     * @param array $filters
     * @return array
     * @deprecated
     */
    #[ArrayShape([
        'count' => 'int',
        'total' => 'int'
    ])]
    // todo: refactor to new search/count
    public static function countDepecrecated(int $startTs, int $endTs, array $filters = []): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $query = self::buildSearchQuery($startTs, $endTs, $filters);
        $tables = $query['tables'];
        $conditions = $query['conditions'];
        $params = $query['params'];

        $query = "
            SELECT COUNT(*) as count, SUM(`amount`) as total
            FROM " . implode(", ", $tables) .
            " WHERE " . implode(" AND ", $conditions);

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res['count'] === 0) {
            $res['total'] = 0;
        }
        return $res;
    }

    /**
     * @param int $startTs
     * @param int $endTs
     * @param array $filters
     * @return Transaction[]
     */
    public static function getTransactions(int $startTs, int $endTs, array $filters = []): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $query = self::buildSearchQuery($startTs, $endTs, $filters);
        $tables = $query['tables'];
        $conditions = $query['conditions'];
        $params = $query['params'];
        $query = "
            SELECT t.*
            FROM " . implode(", ", $tables) .
            " WHERE " . implode(" AND ", $conditions);

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
    }

    /**
     * get all transactions of all types for this user
     * @param User $user
     * @return array
     */
    public static function getAllFromUser(User $user): array
    {
        $transactions = [];
        foreach (Stack::getClassesInPackageNamespaces('TN_Billing\Model\Transaction') as $class) {
            $transactions = array_merge($transactions, $class::getFromUser($user));
        }
        return self::sortResults($transactions);
    }

    /**
     * get all transactions for a specific subscription
     * @param Subscription $subscription
     * @return array
     */
    public static function getAllFromSubscription(Subscription $subscription): array
    {
        $transactions = [];
        foreach (Stack::getClassesInPackageNamespaces('Model\Billing\Transaction') as $class) {
            $transactions = array_merge($transactions, $class::getFromSubscription($subscription));
        }
        return self::sortResults($transactions);
    }

    public static function getAllCounts(SearchArguments $search): CountAndTotalResult
    {
        $result = new CountAndTotalResult(0, 0.0);
        foreach (Stack::getClassesInPackageNamespaces('Model\Billing\Transaction') as $class) {
            $typeResult = $class::countAndTotal($search, 'amount');
            $result->count += $typeResult->count;
            $result->total += $typeResult->total;
        }
        return $result;
    }

    /**
     * @param int $startTs
     * @param $endTs
     * @param array $filters
     * @return Transaction[]
     */
    public static function getAllTransactions(int $startTs, $endTs, array $filters = []): array
    {
        $transactions = [];
        foreach (Stack::getClassesInPackageNamespaces('Model\Billing\Transaction') as $class) {
            $transactions = array_merge($transactions, $class::getTransactions($startTs, $endTs, $filters));
        }
        return $transactions;
    }

    /**
     * sort all the results - always the same - most recent first
     * @param array $transactions
     * @return array
     */
    protected static function sortResults(array $transactions): array
    {
        $timestamps = [];
        foreach ($transactions as $transaction) {
            $timestamps[] = $transaction->ts ?? 0;
        }
        array_multisort($timestamps, SORT_DESC, $transactions);
        return $transactions;
    }
}
