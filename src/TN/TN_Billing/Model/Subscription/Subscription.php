<?php

namespace TN\TN_Billing\Model\Subscription;

use PDO;
use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Gateway\Braintree;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Plan\Price;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Billing\Trait\GetBillingCycle;
use TN\TN_Billing\Trait\GetGateway;
use TN\TN_Billing\Trait\GetPlan;
use TN\TN_Core\Attribute\Constraints\Inclusion;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\CountAndTotalResult;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonArgument;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonOperator;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Trait\GetUser;

/**
 * record a user's subscription to a plan and billing cycle
 *
 * @see \TN\TN_Billing\Model\Subscription\Plan\Plan
 * @see \TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle
 * @see \TN\TN_Billing\Model\Gateway\Gateway
 * @see \TN\TN_Billing\Model\Transaction\Transaction
 *
 */
#[TableName('subscriptions')]
class Subscription implements Persistence
{
    use MySQL;
    use PersistentModel;
    use GetPlan;
    use GetBillingCycle;
    use GetGateway;
    use GetUser;

    const int NOTIFY_UPCOMING_TRANSACTION_WITHIN_MAX = 86400 * 15;
    const int USER_CHAIN_ALLOWED_INTERVAL = 86400;

    /** @var int user's id */
    public int $userId;

    /** @var bool is the subscription activated, ie, paid for? To be used, it must also be within the timestamp dates */
    public bool $active = false;

    /** @var string plan key */
    public string $planKey;

    /** @var string billing cycle key */
    public string $billingCycleKey;

    /** @var string the key of the corresponding gateway */
    public string $gatewayKey;

    /** @var int id of a voucher code, if one was used */
    public int $voucherCodeId = 0;

    /** @var int id of a campaign, if one was used */
    public int $campaignId = 0;

    /** @var int when the subscription starts */
    public int $startTs;

    /** @var int when does the subscription end? 0 = forever */
    public int $endTs = 0;

    /** @var int total number of successful transactions */
    public int $numTransactions = 0;

    /** @var float how much the last successful transaction was for */
    public float $lastTransactionAmount = 0.00;

    /** @var int when the last successful transaction was */
    public int $lastTransactionTs = 0;

    /** @var int timestamp for the next transaction */
    public int $nextTransactionTs = 0;

    /** @var int when the user was last notified of an upcoming transaction */
    public int $upcomingTransactionLastNotified = 0;

    /** @var float this is saved on the subscription so that a price change after the upcoming transaction email does not affect how much we charge the users */
    public float $nextTransactionAmount = 0.00;

    /** @var int the last time a recurring payment failed */
    public int $lastTransactionFailure = 0;

    /** @var string why did the subscription end? */
    #[Inclusion(['', 'refunded', 'user-cancelled', 'payment-failed', 'expired', 'reorganization', 'upgraded'])]
    public string $endReason = '';

    /** @var array this will only be populated if you invoke getUserSubscriptions with $includeTransactions parameter set to true */
    #[Impersistent]
    public array $transactions = [];

    /** @return string[] get the possible end reason options */
    public static function getEndReasonOptions(): array
    {
        return [
            'user-cancelled' => 'Cancelled',
            'payment-failed' => 'Unable to process payment',
            'reorganization' => 'Prevent concurrent subscriptions',
            'upgraded' => 'Upgraded',
            'refunded' => 'Refunded'
        ];
    }

    /**
     * @param User $user
     * @param string $gatewayKey
     * @param string $planKey
     * @param string $billingCycleKey
     * @param int $maxEndTs
     * @return Subscription|null
     */
    public static function getExtendableUserSubscriptionByGateway(
        User   $user,
        string $gatewayKey,
        string $planKey,
        string $billingCycleKey,
        int $maxEndTs
    ): ?Subscription {
        return static::searchOne(new SearchArguments([
            new SearchComparison('`userId`', '=', $user->id),
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`endTs`', '<=', $maxEndTs),
            new SearchComparison('`gatewayKey`', '=', $gatewayKey),
            new SearchComparison('`planKey`', '=', $planKey),
            new SearchComparison('`billingCycleKey`', '=', $billingCycleKey)
        ]));
    }

    public static function getUserActiveSubscription(User $user, string $gatewayKey = ''): ?Subscription
    {
        $conditions = [
            new SearchComparison('`userId`', '=', $user->id),
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`startTs`', '<=', Time::getNow()),
            new SearchLogical('OR', [
                new SearchComparison('`endTs`', '=', 0),
                new SearchComparison('`endTs`', '>', Time::getNow())
            ])
        ];

        if (!empty($gatewayKey)) {
            $conditions[] = new SearchComparison('`gatewayKey`', '=', $gatewayKey);
        }

        $subscriptions = static::search(new SearchArguments(new SearchLogical('AND', $conditions)));

        if (!count($subscriptions)) {
            return null;
        }
        $levels = [];
        foreach ($subscriptions as $subscription) {
            $levels[] = $subscription->getPlan() instanceof Plan ? $subscription->getPlan()->level : 0;
        }
        array_multisort($levels, SORT_DESC, $subscriptions);
        return $subscriptions[0];
    }

    /**
     * @param User $user
     * @return array
     */
    public static function getUsersActiveAndFutureSubscriptions(User $user): array
    {
        return static::search(new SearchArguments([
            new SearchComparison('`userId`', '=', $user->id),
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`startTs`', '<=', Time::getNow()),
            new SearchLogical('OR', [
                new SearchComparison('`endTs`', '=', 0),
                new SearchComparison('`endTs`', '>', Time::getNow())
            ])
        ]));
    }

    /**
     * @param User $user
     * @param Plan $plan
     * @param int $exceptId
     * @return ?Subscription
     */
    public static function getCreditableUserSubscription(User $user, Plan $plan, int $exceptId = 0): ?Subscription
    {
        $subscription = self::getUserActiveSubscription($user);
        if ($subscription instanceof Subscription && $plan->level >= $subscription->getPlan()->level && $subscription->id !== $exceptId) {
            return $subscription;
        }
        $subscription = self::getUserActiveSubscription($user, 'rotopass');
        if ($subscription instanceof Subscription && $plan->level >= $subscription->getPlan()->level && $subscription->id !== $exceptId) {
            return $subscription;
        }
        return null;
    }

    /**
     * gets all user's subscriptions, past and present
     * @param User $user
     * @param bool $includeTransactions
     * @return array
     */
    public static function getUserSubscriptions(User $user, bool $includeTransactions = false): array
    {
        $subscriptions = static::search(new SearchArguments([
            new SearchComparison('`userId`', '=', $user->id)
        ]));
        if (!empty($subscriptions) && $includeTransactions) {
            $transactions = Transaction::getAllFromUser($user);
            foreach ($subscriptions as $subscription) {
                $subscription->transactions = [];
                foreach ($transactions as $transaction) {
                    if ($subscription->id === ($transaction->subscriptionId ?? 0)) {
                        $subscription->transactions[] = $transaction;
                    }
                }
            }
        }
        return $subscriptions;
    }

    /** @return Subscription[] all subscriptions that have an upcoming renewal that haven't been notified for it */
    public static function getUnNotifiedUpcomingRenewals(): array
    {
        return static::search(new SearchArguments([
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`endTs`', '=', 0),
            new SearchComparison('`nextTransactionTs`', '<', Time::getNow()),
            new SearchComparison('`upcomingTransactionLastNotified`', '<', Time::getNow() - self::NOTIFY_UPCOMING_TRANSACTION_WITHIN_MAX),
            new SearchComparison('`gatewayKey`', '=', 'braintree')
        ]));
    }

    /**
     * @param string $type
     * @param int $startTs
     * @param int $endTs
     * @return array
     */
    public static function countNewByGateway(int $startTs, int $endTs): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $params = [];
        $query = "
            SELECT gatewayKey, COUNT(*) as count
            FROM {$table}
            ";
        $conditions = [];
        $conditions[] = "`active` = 1";
        $conditions[] = "`lastTransactionTs` > ?";
        $params[] = $startTs;
        $conditions[] = "`lastTransactionTs` < ?";
        $params[] = $endTs;
        $conditions[] = "`numTransactions` = 1";
        $query .= " WHERE " . implode(" AND ", $conditions);
        $query .= " GROUP BY `gatewayKey`";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $counts = [];
        while ($res = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[$res['gatewayKey']] = $res['count'];
        }
        return $counts;
    }

    public static function countAndTotalByType(
        string $type,
        int $startTs,
        int $endTs,
        string $planKey = '',
        string $billingCycleKey = '',
        string $gatewayKey = '',
        ?string $endReason = null,
        ?int $campaignId = null
    ): CountAndTotalResult {
        $conditions = [];

        $conditions[] = new SearchComparison(
            argument1: new SearchComparisonArgument(property: 'active'),
            operator: SearchComparisonOperator::Equals,
            argument2: new SearchComparisonArgument(value: 1)
        );

        switch ($type) {
            case 'new':
                $conditions[] = new SearchComparison('`startTs`', '>=', $startTs);
                $conditions[] = new SearchComparison('`startTs`', '<', $endTs);
                break;
            case 'renewal':
                $conditions[] = new SearchComparison('`lastTransactionTs`', '>=', $startTs);
                $conditions[] = new SearchComparison('`lastTransactionTs`', '<', $endTs);
                $conditions[] = new SearchComparison('`numTransactions`', '>', 1);
                break;
            case 'stalled':
                $conditions[] = new SearchComparison('`lastTransactionFailure`', '>', '`nextTransactionTs`');
                $conditions[] = new SearchComparison('`lastTransactionFailure`', '>=', $startTs);
                $conditions[] = new SearchComparison('`lastTransactionFailure`', '<', $endTs);
                break;
            case 'ended':
                $conditions[] = new SearchComparison('`endTs`', '>=', $startTs);
                $conditions[] = new SearchComparison('`endTs`', '<', $endTs);
                break;
            default:
                return new CountAndTotalResult(0, 0.0);
        }

        if (!empty($planKey)) {
            $conditions[] = new SearchComparison('`planKey`', '=', $planKey);
        }
        if (!empty($billingCycleKey)) {
            $conditions[] = new SearchComparison('`billingCycleKey`', '=', $billingCycleKey);
        }
        if (!empty($gatewayKey)) {
            $conditions[] = new SearchComparison('`gatewayKey`', '=', $gatewayKey);
        }
        if ($endReason !== null) {
            $conditions[] = new SearchComparison('`endReason`', '=', $endReason);
        }

        if ($campaignId !== null) {
            $conditions[] = new SearchComparison('`campaignId`', '=', $campaignId);
        }

        return static::countAndTotal(new SearchArguments(conditions: $conditions), 'lastTransactionAmount');
    }

    /**
     * @param int $ts
     * @param string $planKey
     * @param string $billingCycleKey
     * @param string $gatewayKey
     * @return int
     */
    public static function countActive(int $ts, string $planKey = '', string $billingCycleKey = '', string $gatewayKey = ''): int
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $params = [];
        $query = "
            SELECT COUNT(*) as count FROM {$table}";
        $conditions = [];
        $conditions[] = "`active` = 1";

        $conditions[] = "(`endTs` >= ? OR endTs = 0)";
        $params[] = $ts;
        $conditions[] = "`startTs` <= ?";
        $params[] = $ts;

        if (!empty($planKey)) {
            $conditions[] = "`planKey` = ?";
            $params[] = $planKey;
        }
        if (!empty($billingCycleKey)) {
            $conditions[] = "`billingCycleKey` = ?";
            $params[] = $billingCycleKey;
        }
        if (!empty($gatewayKey)) {
            $conditions[] = "`gatewayKey` = ?";
            $params[] = $gatewayKey;
        }

        $query .= " WHERE " . implode(" AND ", $conditions);

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res['count'] ?? 0;
    }

    public static function countNewByCampaigns(int $startTs, int $endTs): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $query = "
            SELECT s.voucherCodeId, s.planKey, count(*) as count, c.key, SUM(`lastTransactionAmount`) as total
            FROM {$table} as s, campaigns as c
            WHERE s.campaignId = c.id
            AND s.lastTransactionTs > ?
            AND s.lastTransactionTs < ?
            AND s.numTransactions = 1
            AND s.active = 1
            GROUP BY s.campaignId, s.planKey";
        $stmt = $db->prepare($query);
        $stmt->execute([$startTs, $endTs]);
        $campaigns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($campaigns[$row['key']])) {
                $campaigns[$row['key']] = [];
            }
            $campaigns[$row['key']][$row['planKey']] = [
                'count' => $row['count'],
                'total' => $row['total']
            ];
        }
        return $campaigns;
    }

    /** @param Transaction $transaction associate the transaction with this subscription
     * @throws ValidationException
     */
    public function addSuccessfulTransaction(Transaction $transaction): void
    {
        $this->update([
            'lastTransactionAmount' => $transaction->ts > $this->lastTransactionTs ? $transaction->amount : $this->lastTransactionAmount,
            'lastTransactionTs' => $transaction->ts,
            'numTransactions' => $this->numTransactions + 1
        ]);
    }

    /**
     * @param Subscription $subscription
     * convenience method that returns an array of prices given a subscription
     * factors in voucher code discounts
     * @return array
     */

    public static function getSubscriptionPrices(Subscription $subscription): array
    {
        $plan = Plan::getInstanceByKey($subscription->planKey);
        $monthlyPrice = $plan->getPrice(BillingCycle::getInstanceByKey('monthly'));
        $annuallyPrice = $plan->getPrice(BillingCycle::getInstanceByKey('annually'));
        if ($subscription->voucherCodeId > 0) {
            $voucherCode = VoucherCode::readFromId($subscription->voucherCodeId);
            if ($voucherCode instanceof VoucherCode && $voucherCode->numTransactions === 0) {
                $monthlyPrice = $voucherCode->applyToPrice($monthlyPrice->price);
                $annuallyPrice = $voucherCode->applyToPrice($annuallyPrice->price);
            }
        }
        return array('monthly' => $monthlyPrice->price, 'annually' => $annuallyPrice->price);
    }

    /** @return int notify users of an upcoming transaction within these many seconds of said transaction */
    protected function getNotifyUpcomingTransactionWithin(): int
    {
        $days = self::NOTIFY_UPCOMING_TRANSACTION_WITHIN_MAX / Time::ONE_DAY;
        if ($this->getBillingCycle()->notifyUpcomingTransactionWithinDays) {
            $days = $this->getBillingCycle()->notifyUpcomingTransactionWithinDays;
        }
        return $days * Time::ONE_DAY;
    }

    /**
     * @return void notify the user of the upcoming renewal
     * @throws ValidationException|\TN\TN_Core\Error\TNException
     */
    public function notifyUpcomingRenewal(): void
    {
        // refuse to do if it no longer meets the conditions
        if (
            $this->nextTransactionTs >= (Time::getNow() + $this->getNotifyUpcomingTransactionWithin()) ||
            $this->nextTransactionTs <= Time::getNow() ||
            $this->upcomingTransactionLastNotified >= Time::getNow() - $this->getNotifyUpcomingTransactionWithin()
        ) {
            return;
        }

        // get what the price will be
        $price = $this->getPlan()->getPrice($this->getBillingCycle())->price;
        $user = $this->getUser();
        $customer = Customer::getFromUser($this->getUser());

        if ($this->voucherCodeId > 0) {
            $voucherCode = VoucherCode::readFromId($this->voucherCodeId);
            if ($voucherCode instanceof VoucherCode && $voucherCode->numTransactions === 0) {
                $price = $voucherCode->applyToPrice($price);
            }
        }

        // send the email to notify them
        $res = Email::sendFromTemplate(
            //            'Your ' . $_ENV['SITE_NAME'] . ' Subscription Is About To Renew',
            'subscription/subscription/upcomingrenewal',
            $user->email,
            [
                'subscription' => $this,
                'username' => $user->username,
                'planName' => $this->getPlan()->name,
                'billingCycleName' => $this->getBillingCycle()->name,
                'price' => $price
            ]
        );

        if ($res) {
            // update this to have that price and that we notified them
            $this->update([
                'nextTransactionAmount' => $price,
                'upcomingTransactionLastNotified' => Time::getNow()
            ]);
        }
    }

    public static function checkAutoRenewDates(): void
    {
        $subscriptions = static::search(new SearchArguments([
            new SearchComparison('`gatewayKey`', '=', 'braintree'),
            new SearchComparison('`nextTransactionTs`', '>', 0),
            new SearchComparison('`active`', '=', 1)
        ]));
        foreach ($subscriptions as $sub) {
            $billingCycle = BillingCycle::getInstanceByKey($sub->billingCycleKey);
            if (!$billingCycle) {
                continue;
            }
            $fromLastTransaction = $billingCycle->getNextTs($sub->lastTransactionTs);
            $fromStartDate = $billingCycle->getNextTs($sub->startTs);
            $nextTransactionTs = max($fromLastTransaction, $fromStartDate);
            if ($nextTransactionTs !== $sub->nextTransactionTs) {
                $sub->update([
                    'nextTransactionTs' => $nextTransactionTs
                ]);
            }
        }
    }

    /** 
     * @return array all subscriptions that are due a recurring bill
     * 
     * INCLUDES FAILSAFE: Will NOT return subscriptions for users who have had 
     * ANY transaction in the last 24 hours, preventing duplicate/rapid charges.
     */
    public static function getRecurringDueSubscriptions(): array
    {
        self::checkAutoRenewDates();
        $dueSubscriptions = static::search(new SearchArguments([
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`startTs`', '<=', Time::getNow()),
            new SearchComparison('`endTs`', '=', 0),
            new SearchComparison('`nextTransactionTs`', '<', Time::getNow()),
            new SearchComparison('`lastTransactionFailure`', '<', '`nextTransactionTs`'),
            new SearchComparison('`gatewayKey`', '=', 'braintree')
        ]));

        // FAILSAFE: Filter out subscriptions for users who have had ANY transaction in the last 24 hours
        $safeSubscriptions = [];
        $skippedCount = 0;
        foreach ($dueSubscriptions as $subscription) {
            if (!self::userHasRecentTransaction($subscription->userId)) {
                $safeSubscriptions[] = $subscription;
            } else {
                $skippedCount++;
                error_log("FAILSAFE: Skipping auto-renewal for subscription ID {$subscription->id} (user {$subscription->userId}) due to recent transaction activity");
            }
        }

        if ($skippedCount > 0) {
            error_log("FAILSAFE: Skipped {$skippedCount} subscription auto-renewals due to recent transaction activity");
        }

        return $safeSubscriptions;
    }

    /**
     * Check if a user has any transaction in the last 24 hours
     * FAILSAFE: Prevents auto-renewal if user has recent payment activity
     * 
     * @param int $userId
     * @return bool true if user has transaction in last 24 hours, false otherwise
     */
    private static function userHasRecentTransaction(int $userId): bool
    {
        $twentyFourHoursAgo = Time::getNow() - (24 * 60 * 60); // 24 hours in seconds

        // Check all transaction types across all gateways
        foreach (Stack::getClassesInPackageNamespaces('Model\Billing\Transaction') as $transactionClass) {
            try {
                // Search for any transactions by this user in the last 24 hours
                $recentTransactions = $transactionClass::search(new SearchArguments([
                    new SearchComparison('`userId`', '=', $userId),
                    new SearchComparison('`ts`', '>=', $twentyFourHoursAgo),
                    new SearchComparison('`ts`', '<=', Time::getNow())
                ]));

                // If any transactions found, return true (user has recent activity)
                if (!empty($recentTransactions)) {
                    return true;
                }
            } catch (\Exception $e) {
                // If search fails for this transaction type, log and continue
                // Better to be safe and skip than to risk duplicate charges
                error_log("Failed to check recent transactions for class {$transactionClass}: " . $e->getMessage());
            }
        }

        return false; // No recent transactions found
    }

    /**
     * @return Transaction attempt recurring billing
     * @throws ValidationException
     */
    public function recurBilling(string|false $nonce = false, string|false $deviceData = false): Transaction
    {
        // Skip subscription if user doesn't exist (likely merged/deleted)
        if (!$this->getUser()) {
            throw new ValidationException('User not found (likely merged/deleted) - skipping subscription');
        }

        $errors = [];
        if ($this->nextTransactionTs > Time::getNow()) {
            $errors[] = 'Next transaction time is not in the past';
        }

        if ($this->endTs !== 0) {
            $errors[] = 'The subscription already has its end date fixed';
        }

        if (!$this->active) {
            $errors[] = 'Subscription is not active';
        }

        if ($this->getGateway()->key !== 'braintree') {
            $errors[] = 'Cannot recur billing if the gateway is not Braintree';
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $plan = $this->getPlan();
        $billingCycle = $this->getBillingCycle();
        $price = $this->nextTransactionAmount;
        if ($price == 0) {
            $price = $plan->getPrice($billingCycle)->price;
            if ($this->voucherCodeId > 0) {
                $voucherCode = VoucherCode::readFromId($this->voucherCodeId);
                if ($voucherCode instanceof VoucherCode && $voucherCode->numTransactions === 0) {
                    $price = $voucherCode->applyToPrice($price);
                }
            }
        }

        $creditableSubscription = Subscription::getCreditableUserSubscription($this->getUser(), $this->getPlan(), $this->id);
        if ($creditableSubscription instanceof Subscription) {
            $credit = $creditableSubscription->getCreditAmount();
        } else {
            $credit = 0;
        }

        $transaction = \TN\TN_Billing\Model\Transaction\Braintree\Transaction::getInstance();
        $user = $this->getUser();
        $amount = round($price - $credit, 2);
        $transaction->update([
            'userId' => $user instanceof User ? $user->id : 0,
            'amount' => $amount,
            'voucherCodeId' => 0,
            'discount' => $credit,
            'subscriptionId' => $this->id,
            'ip' => 'from-server'
        ]);

        // execute transaction
        $transaction->execute(
            [
                'name' => $_ENV['SITE_NAME'] . '*' . $plan->name,
                'url' => $_ENV['BASE_URL']
            ],
            [
                [
                    'description' => $plan->description,
                    'discountAmount' => $credit,
                    'kind' => 'debit',
                    'name' => $plan->name . ' ' . $billingCycle->name,
                    'quantity' => 1,
                    'unitAmount' => $amount,
                    'totalAmount' => $amount
                ]
            ],
            $nonce,
            $deviceData
        );

        // on failure, email
        if (!$transaction->success) {

            $this->update([
                'lastTransactionFailure' => Time::getNow()
            ]);

            if ($nonce === false) {
                // we are only doing this if we can't immediately inform the user via the screen
                // (ie, because this is invoked via update payment method logic rather than a cron run)
                Email::sendFromTemplate(
                    //                    $_ENV['SITE_NAME'] . ' Subscription Payment Failed - Action Needed!',
                    'subscription/subscription/paymentfailed',
                    $user->email,
                    [
                        'username' => $user->username,
                        'planName' => $this->getPlan()->name
                    ]
                );
            }

            throw new ValidationException($transaction->errorMsg ?? 'Unknown error occurred');
        }

        // on success, update here, and email
        try {
            $this->update([
                'nextTransactionTs' => $billingCycle->getNextTs(Time::getNow()),
                'upcomingTransactionLastNotified' => 0,
                'nextTransactionAmount' => 0.00
            ]);
        } catch (ValidationException $e) {
            $transaction->refund();
            throw new ValidationException('An error occurred while setting the subscription\'s next billing date');
        }

        // Record successful transaction FIRST before attempting to end creditable subscription
        $this->addSuccessfulTransaction($transaction);

        if ($creditableSubscription instanceof Subscription) {
            try {
                $creditableSubscription->upgradedToNewSubscription();
            } catch (ValidationException $e) {
                // Log but don't fail - some gateways (like RotoPass) can't be ended by the system
                error_log("Could not end creditable subscription {$creditableSubscription->id}: " . $e->getMessage());
            }
        }

        // notify the user
        $user->subscriptionsChanged();

        Email::sendFromTemplate(
            'subscription/subscription/recurringpaymentprocessed',
            $user->email,
            [
                'nextTransactionTs' => $this->nextTransactionTs,
                'amount' => $transaction->amount,
                'username' => $user->username,
                'planName' => $this->getPlan()->name,
                'billingCycleName' => $this->getBillingCycle()->name
            ]
        );

        return $transaction;
    }

    /** @return array get all the subscriptions where a payment has failed, and the grace period has expired */
    public static function getFailedBillingsBeyondGracePeriod(): array
    {
        $subscriptions = static::search(new SearchArguments([
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`endTs`', '=', 0),
            new SearchComparison('`nextTransactionTs`', '<', Time::getNow()),
            new SearchComparison('`gatewayKey`', '=', 'braintree')
        ]));
        $beyondGracePeriodSubscriptions = [];
        foreach ($subscriptions as $subscription) {
            if ($subscription->beyondGracePeriod()) {
                $beyondGracePeriodSubscriptions[] = $subscription;
            }
        }
        return $beyondGracePeriodSubscriptions;
    }

    /** @return bool beyond grace period allowed of billing cycle? */
    public function beyondGracePeriod(): bool
    {
        return $this->lastTransactionFailure > $this->nextTransactionTs &&
            ($this->nextTransactionTs + ($this->getBillingCycle()->numDaysGracePeriod * 86400)) < Time::getNow();
    }

    /** @return bool subscription is in grace period */
    public function inGracePeriod(): bool
    {
        return $this->lastTransactionFailure > $this->nextTransactionTs &&
            ($this->nextTransactionTs + ($this->getBillingCycle()->numDaysGracePeriod * 86400)) > Time::getNow();
    }

    /** @return bool whether the payment is overdue or not */
    public function hasOverduePayment(): bool
    {
        return $this->lastTransactionFailure > $this->nextTransactionTs;
    }

    /** just inserted - email the subscriber if it was created active */
    protected function afterSaveInsert(): void
    {
        if ($this->active) {
            $this->sendWelcomeEmail();
        }
    }

    /** @param array $changedProperties after a save update */
    protected function afterSaveUpdate(array $changedProperties): void
    {
        if (in_array('active', $changedProperties) && $this->active) {
            // if it's within 24 hours of the startTs, let's welcome the user. It's possible otherwise that
            // a failed payment led to the subscription being made inactive and they just updated their payment.
            if ($this->startTs < (Time::getNow() + 86400)) {
                $this->sendWelcomeEmail();
            }
        }
    }

    protected function sendWelcomeEmail(): bool
    {
        $user = $this->getUser();
        if (in_array($this->gatewayKey, ['rotopass', 'apple', 'google'])) {
            return Email::sendFromTemplate(
                'subscription/subscription/welcome/' . $this->gatewayKey,
                $user->email,
                [
                    'username' => $user->username,
                    'planName' => $this->getPlan()->name,
                    'billingCycle' => $this->getBillingCycle()->name
                ]
            );
        }
        return Email::sendFromTemplate(
            'subscription/subscription/welcome',
            $user->email,
            [
                'nextTransactionTs' => $this->nextTransactionTs,
                'username' => $user->username,
                'planName' => $this->getPlan()->name,
                'billingCycle' => $this->getBillingCycle()->name
            ]
        );
    }

    /** @return bool if the subscription already has an end date, it shouldn't be possible to cancel it! */
    public function hasEndTs(): bool
    {
        return $this->endTs > 0;
    }

    /**
     * bring the subscription to an end
     * @param string $reason
     * @param bool $immediateEnd
     * @return void
     * @throws ValidationException
     */
    public function end(string $reason, bool $immediateEnd = false): void
    {
        if ($this->hasEndTs() && !$immediateEnd) {
            throw new ValidationException('This subscription already has an end date');
        }
        if (!$this->getGateway()->mutableSubscriptions) {
            throw new ValidationException('This subscription cannot be ended');
        }
        $billingCycle = BillingCycle::getInstanceByKey($this->billingCycleKey);
        $this->update([
            /*
             * be as generous as we can to the user with the end date - the latest of:
             * the current time
             * the next transaction date
             * billing cycle next Ts from start (e.g. for a free subscription)
             */
            'endTs' => $immediateEnd ? Time::getNow() : max($this->nextTransactionTs, Time::getNow(), $billingCycle->getNextTs($this->startTs)),
            'endReason' => $reason,
            'nextTransactionTs' => 0
        ]);
    }

    /**
     * move the subscription into the future
     * @param int $startTs
     * @return void
     * @throws ValidationException
     * @see \TN\TN_Billing\Model\Subscription\SubscriptionOrganizer
     */
    public function migrateStartTsIntoFuture(int $startTs): void
    {
        if ($startTs <= $this->startTs) {
            return;
        }
        $diff = $startTs - $this->startTs;
        $update = [
            'startTs' => $startTs
        ];
        if ($this->nextTransactionTs > 0) {
            $update['nextTransactionTs'] = $this->nextTransactionTs + $diff;
        }
        if ($this->endTs > 0) {
            $update['endTs'] = $this->endTs + $diff;
        }
        $this->update($update);
    }

    /**
     * @return void the subscription can be terminated due to an equivalent subscription
     * @throws ValidationException
     */
    public function endDueToReorganization(): void
    {
        if ($this->endTs > 0) {
            return;
        }
        $this->end('reorganization');
    }

    /**
     * @return void the subscription was used as a credit to purchase an upgrade so can be truncated without refund
     * @throws ValidationException
     */
    public function upgradedToNewSubscription(): void
    {
        if ($this->endTs > 0) {
            return;
        }
        $this->end('upgraded', true);
    }

    /**
     * @return void cancel the subscription (triggered by the user!)
     * @throws ValidationException
     */
    public function cancel(): void
    {
        $this->end('user-cancelled');
        $user = $this->getUser();
        $user->subscriptionsChanged();
        Email::sendFromTemplate(
            'subscription/subscription/cancelled',
            $user->email,
            [
                'planName' => $this->getPlan()->name,
                'username' => $user->username,
                'endTs' => $this->endTs
            ]
        );
    }

    /**
     * @return void end the subscription due to a definitely failed payment
     * @throws ValidationException
     */
    public function paymentFailedAndGracePeriodExpired(): void
    {
        $this->end('payment-failed', true);
        $user = $this->getUser();
        $user->subscriptionsChanged();
        Email::sendFromTemplate(
            'subscription/subscription/paymentfailedandgraceperiodexpired',
            $user->email,
            [
                'planName' => $this->getPlan()->name,
                'username' => $user->username
            ]
        );
    }

    /**
     * @return void gift/complimentary subscription has expired
     * @throws ValidationException
     */
    public function expire(): void
    {
        $this->end('expired');
        $user = $this->getUser();
        $user->subscriptionsChanged();
        Email::sendFromTemplate(
            'subscription/subscription/expired',
            $user->email,
            [
                'username' => $this->getUser()->username
            ]
        );
    }

    /**
     * @return float if we cancelled this subscription and truncated its end date, how many $ is available to the user as credit?
     */
    public function getCreditAmount(): float
    {
        if ($this->nextTransactionTs <= Time::getNow() && $this->nextTransactionTs > 0) {
            return 0;
        }

        $transactions = Transaction::getAllFromSubscription($this);
        $nonRefundedTransactions = [];
        foreach ($transactions as $transaction) {
            if ($transaction->success && !$transaction->refunded) {
                $nonRefundedTransactions[] = $transaction;
            }
        }

        $price = $this->getPlan()->getPrice($this->getBillingCycle());
        if (!($price instanceof Price)) {
            return 0;
        }
        $price = $price->price;

        if (empty($nonRefundedTransactions)) {
            $sinceTs = $this->startTs;
        } else {
            $sinceTs = $nonRefundedTransactions[0]->ts;
        }

        $untilTs = $this->endTs > 0 ? $this->endTs : ($this->nextTransactionTs > 0 ? $this->nextTransactionTs : $this->getBillingCycle()->getNextTs($this->startTs));
        $sinceTs = max($sinceTs, $this->getBillingCycle()->getPreviousTs($untilTs));

        $totalDays = max(round(($untilTs - $sinceTs) / 86400), 1);
        $daysLeft = ceil(($untilTs - Time::getNow()) / 86400);
        return round(min($price, max(0, ($daysLeft / $totalDays) * $price)), 2);
    }

    /**
     * get the start of the subscription "chain"
     * @return Subscription
     */
    public function getUserSubscriptionChainStart(): Subscription
    {
        $sub = $this;
        while (true) {
            $previous = $sub->getUserSubscriptionChainPrevious();
            if (!$previous) {
                return $sub;
            }
            $sub = $previous;
        }
    }

    /**
     * get the previous subscription in the "chain", if one exists
     * @return Subscription|null
     */
    protected function getUserSubscriptionChainPrevious(): ?Subscription
    {
        return static::searchOne(new SearchArguments([
            new SearchComparison('`userId`', '=', $this->userId),
            new SearchComparison('`endTs`', '<', $this->startTs),
            new SearchComparison('`endTs`', '>', $this->startTs - self::USER_CHAIN_ALLOWED_INTERVAL),
            new SearchComparison('`endTs`', '>', '`startTs`'),
            new SearchComparison('`active`', '=', 1)
        ]));
    }

    /**
     * get the end of the subscription "chain"
     * @return Subscription
     */
    public function getUserSubscriptionChainEnd(): Subscription
    {
        $sub = $this;
        while (true) {
            $next = $sub->getUserSubscriptionChainNext();
            if (!$next) {
                return $sub;
            }
            $sub = $next;
        }
    }

    /**
     * get the previous subscription in the "chain", if one exists
     * @return Subscription|null
     */
    protected function getUserSubscriptionChainNext(): ?Subscription
    {
        return static::searchOne(new SearchArguments([
            new SearchComparison('`userId`', '=', $this->userId),
            new SearchComparison('`startTs`', '>', $this->endTs),
            new SearchComparison('`startTs`', '<', $this->endTs + self::USER_CHAIN_ALLOWED_INTERVAL),
            new SearchLogical('OR', [
                new SearchComparison('`endTs`', '>', '`startTs`'),
                new SearchComparison('`endTs`', '=', 0)
            ]),
            new SearchComparison('`active`', '=', 1)
        ]));
    }
}
