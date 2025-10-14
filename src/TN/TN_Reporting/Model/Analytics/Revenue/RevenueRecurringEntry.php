<?php

namespace TN\TN_Reporting\Model\Analytics\Revenue;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;
use PDO;

#[TableName('analytics_revenue_recurring_entries')]
class RevenueRecurringEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = ['gateway', 'plan', 'billingCycle'];

    /** @var string|null */
    public ?string $gatewayKey = null;

    /** @var string|null */
    public ?string $planKey = null;

    /** @var string|null */
    public ?string $billingCycleKey = null;

    /** @var float */
    public float $monthlyRecurringRevenue = 0;

    /** @var float */
    public float $annualRecurringRevenue = 0;


    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating recurring revenue report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;
        $data = [];

        $endTs = strtotime(date('Y-m-d 23:59:59', $this->dayTs));

        // use datetime object to subtract a month
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->dayTs);
        $datetime->modify('-1 month');
        $monthlyStartTs = $datetime->getTimestamp();

        // do the same for annual
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->dayTs);
        $datetime->modify('-1 year');
        $annualStartTs = $datetime->getTimestamp();

        // Build conditions for monthly recurring revenue
        $monthlyConditions = [
            new SearchComparison('`ts`', '>=', $monthlyStartTs),
            new SearchComparison('`ts`', '<=', $endTs),
            new SearchComparison('`success`', '=', true),
            new SearchComparison('`subscriptionId`', '>', 0) // recurring transactions
        ];

        // Build conditions for annual recurring revenue
        $annualConditions = [
            new SearchComparison('`ts`', '>=', $annualStartTs),
            new SearchComparison('`ts`', '<=', $endTs),
            new SearchComparison('`success`', '=', true),
            new SearchComparison('`subscriptionId`', '>', 0) // recurring transactions
        ];

        // Add subscription table joins and filters if plan or billing cycle is specified
        if (!empty($this->planKey) || !empty($this->billingCycleKey)) {
            // We need to use a custom approach that properly handles subscription filtering
            // Build custom conditions that include subscription joins
            $monthlyConditionsWithSub = $monthlyConditions;
            $annualConditionsWithSub = $annualConditions;

            if (!empty($this->planKey)) {
                $monthlyConditionsWithSub[] = new SearchComparison('s.`planKey`', '=', $this->planKey);
                $annualConditionsWithSub[] = new SearchComparison('s.`planKey`', '=', $this->planKey);
            }
            if (!empty($this->billingCycleKey)) {
                $monthlyConditionsWithSub[] = new SearchComparison('s.`billingCycleKey`', '=', $this->billingCycleKey);
                $annualConditionsWithSub[] = new SearchComparison('s.`billingCycleKey`', '=', $this->billingCycleKey);
            }

            if (empty($this->gatewayKey)) {
                // Use custom query for all transaction types with subscription filtering
                $monthlyResult = $this->countTransactionsWithSubscriptionFilter($monthlyStartTs, $endTs, $this->planKey, $this->billingCycleKey);
                $annualResult = $this->countTransactionsWithSubscriptionFilter($annualStartTs, $endTs, $this->planKey, $this->billingCycleKey);
                $data['monthlyRecurringRevenue'] = $monthlyResult['total'];
                $data['annualRecurringRevenue'] = $annualResult['total'];
            } else {
                // Use specific gateway transaction class with custom filtering
                $transactionClass = Gateway::getInstanceByKey($this->gatewayKey)->transactionClass;
                $monthlyResult = $this->countGatewayTransactionsWithSubscriptionFilter($transactionClass, $monthlyStartTs, $endTs, $this->planKey, $this->billingCycleKey);
                $annualResult = $this->countGatewayTransactionsWithSubscriptionFilter($transactionClass, $annualStartTs, $endTs, $this->planKey, $this->billingCycleKey);
                $data['monthlyRecurringRevenue'] = $monthlyResult['total'];
                $data['annualRecurringRevenue'] = $annualResult['total'];
            }
        } else {
            // No plan/billing cycle filtering needed, use existing SearchArguments approach
            if (empty($this->gatewayKey)) {
                // Use getAllCounts for all transaction types
                $monthlyResult = Transaction::getAllCounts(new SearchArguments(conditions: $monthlyConditions));
                $annualResult = Transaction::getAllCounts(new SearchArguments(conditions: $annualConditions));
                $data['monthlyRecurringRevenue'] = $monthlyResult->total;
                $data['annualRecurringRevenue'] = $annualResult->total;
            } else {
                // Use specific gateway transaction class
                $transactionClass = Gateway::getInstanceByKey($this->gatewayKey)->transactionClass;
                $monthlyResult = $transactionClass::countAndTotal(new SearchArguments(conditions: $monthlyConditions), 'amount');
                $annualResult = $transactionClass::countAndTotal(new SearchArguments(conditions: $annualConditions), 'amount');
                $data['monthlyRecurringRevenue'] = $monthlyResult->total;
                $data['annualRecurringRevenue'] = $annualResult->total;
            }
        }
        $this->update($data);
    }

    /**
     * Count transactions across all gateways with subscription filtering
     */
    private function countTransactionsWithSubscriptionFilter(int $startTs, int $endTs, ?string $planKey, ?string $billingCycleKey): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $totalCount = 0;
        $totalAmount = 0.0;

        // Get all transaction classes and sum their results
        foreach (Stack::getClassesInPackageNamespaces('TN_Billing\Model\Transaction') as $transactionClass) {
            $result = $this->countGatewayTransactionsWithSubscriptionFilter($transactionClass, $startTs, $endTs, $planKey, $billingCycleKey);
            $totalCount += $result['count'];
            $totalAmount += $result['total'];
        }

        return ['count' => $totalCount, 'total' => $totalAmount];
    }

    /**
     * Count transactions for a specific gateway with subscription filtering
     */
    private function countGatewayTransactionsWithSubscriptionFilter(string $transactionClass, int $startTs, int $endTs, ?string $planKey, ?string $billingCycleKey): array
    {
        try {
            $db = DB::getInstance($_ENV['MYSQL_DB']);
            $transactionTable = $transactionClass::getTableName();
            $subscriptionTable = Subscription::getTableName();

            $params = [$startTs, $endTs];
            $conditions = [
                "t.`ts` >= ?",
                "t.`ts` <= ?",
                "t.`success` = 1",
                "t.`subscriptionId` > 0",
                "t.`subscriptionId` = s.`id`"
            ];

            if (!empty($planKey)) {
                $conditions[] = "s.`planKey` = ?";
                $params[] = $planKey;
            }

            if (!empty($billingCycleKey)) {
                $conditions[] = "s.`billingCycleKey` = ?";
                $params[] = $billingCycleKey;
            }

            $query = "
                SELECT COUNT(*) as count, SUM(t.`amount`) as total
                FROM {$transactionTable} as t, {$subscriptionTable} as s
                WHERE " . implode(" AND ", $conditions);

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'count' => (int)($result['count'] ?? 0),
                'total' => (float)($result['total'] ?? 0)
            ];
        } catch (\PDOException $e) {
            // Handle case where table doesn't exist (e.g., amember_transactions)
            if (str_contains($e->getMessage(), "doesn't exist")) {
                return ['count' => 0, 'total' => 0.0];
            }
            throw $e;
        }
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];
        $values['gatewayKey'] = [''];
        foreach (Gateway::getInstances() as $gateway) {
            if ($gateway->key !== 'free') {
                $values['gatewayKey'][] = $gateway->key;
            }
        }
        $values['planKey'] = [''];
        foreach (Plan::getInstances() as $plan) {
            if ($plan->paid) {
                $values['planKey'][] = $plan->key;
            }
        }
        $values['billingCycleKey'] = [''];
        foreach (BillingCycle::getInstances() as $billingCycle) {
            $values['billingCycleKey'][] = $billingCycle->key;
        }
        return $values;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        // for each, we want to show total churn, for each of the reasons
        $totals = [
            'monthly' => 0,
            'annual' => 0
        ];

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $totals['monthly'] += $dayReport->monthlyRecurringRevenue;
            $totals['annual'] += $dayReport->annualRecurringRevenue;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'monthly' => $totals['monthly'] > 0 ? $totals['monthly'] / count($dayReports) : 0.0,
            'annual' => $totals['annual'] > 0 ? $totals['annual'] / count($dayReports) : 0.0
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 2,
            'prefix' => '$'
        ];

        return [
            new AnalyticsDataSeriesColumn('annual', 'Annual Run Rate', array_merge($options, ['emphasize' => true, 'chart' => true])),
            new AnalyticsDataSeriesColumn('monthly', 'Monthly Run Rate', $options)
        ];
    }
}
