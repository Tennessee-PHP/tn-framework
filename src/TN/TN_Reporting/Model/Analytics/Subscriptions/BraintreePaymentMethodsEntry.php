<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Braintree\Transaction as BraintreeTransaction;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_braintree_payment_methods_entries')]
class BraintreePaymentMethodsEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = [];

    public int $creditCardCount = 0;
    public int $paypalCount = 0;
    public int $applePayCount = 0;
    public int $googlePayCount = 0;
    public int $otherCount = 0;
    public int $totalCount = 0;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating braintree payment methods report for ' . date('Y-m-d', $this->dayTs) . PHP_EOL;

        $endTs = strtotime('+1 day', $this->dayTs);
        $subscriptionClass = Stack::resolveClassName(Subscription::class);
        $transactionClass = Stack::resolveClassName(BraintreeTransaction::class);

        $subscriptions = $subscriptionClass::search(new SearchArguments(conditions: [
            new SearchComparison('`active`', '=', 1),
            new SearchComparison('`gatewayKey`', '=', 'braintree'),
            new SearchComparison('`startTs`', '>=', $this->dayTs),
            new SearchComparison('`startTs`', '<', $endTs),
        ]));

        $counts = [
            'creditCardCount' => 0,
            'paypalCount' => 0,
            'applePayCount' => 0,
            'googlePayCount' => 0,
            'otherCount' => 0,
            'totalCount' => 0,
        ];

        if (empty($subscriptions)) {
            $this->update($counts);
            return;
        }

        $subscriptionIds = [];
        foreach ($subscriptions as $subscription) {
            $subscriptionIds[] = $subscription->id;
        }

        $transactions = $transactionClass::search(new SearchArguments(
            conditions: [
                new SearchComparison('`success`', '=', 1),
                new SearchComparison('`subscriptionId`', 'IN', $subscriptionIds),
            ],
            sorters: new SearchSorter('ts', 'ASC')
        ));

        $paymentMethodBySubscriptionId = [];
        foreach ($transactions as $transaction) {
            $subscriptionId = (int)$transaction->subscriptionId;
            if ($subscriptionId <= 0 || isset($paymentMethodBySubscriptionId[$subscriptionId])) {
                continue;
            }
            $paymentMethodBySubscriptionId[$subscriptionId] = strtoupper($transaction->paymentMethod ?? '');
        }

        foreach ($subscriptionIds as $subscriptionId) {
            $method = $paymentMethodBySubscriptionId[$subscriptionId] ?? '';
            match ($method) {
                'CREDIT_CARD' => $counts['creditCardCount']++,
                'PAYPAL_ACCOUNT' => $counts['paypalCount']++,
                'APPLE_PAY_CARD' => $counts['applePayCount']++,
                'ANDROID_PAY_CARD' => $counts['googlePayCount']++,
                default => $counts['otherCount']++,
            };
            $counts['totalCount']++;
        }

        $this->update($counts);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        return [];
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        $totals = [
            'creditCardCount' => 0,
            'paypalCount' => 0,
            'applePayCount' => 0,
            'googlePayCount' => 0,
            'otherCount' => 0,
            'totalCount' => 0,
        ];

        foreach ($dayReports as $dayReport) {
            $totals['creditCardCount'] += $dayReport->creditCardCount;
            $totals['paypalCount'] += $dayReport->paypalCount;
            $totals['applePayCount'] += $dayReport->applePayCount;
            $totals['googlePayCount'] += $dayReport->googlePayCount;
            $totals['otherCount'] += $dayReport->otherCount;
            $totals['totalCount'] += $dayReport->totalCount;
        }

        return $totals;
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 0,
            'chart' => true,
        ];

        return [
            new AnalyticsDataSeriesColumn('creditCardCount', 'Card', $options),
            new AnalyticsDataSeriesColumn('paypalCount', 'PayPal', $options),
            new AnalyticsDataSeriesColumn('applePayCount', 'Apple Pay', $options),
            new AnalyticsDataSeriesColumn('googlePayCount', 'Google Pay', $options),
            new AnalyticsDataSeriesColumn('otherCount', 'Other / Unknown', $options),
            new AnalyticsDataSeriesColumn('totalCount', 'Total', [
                'decimals' => 0,
                'emphasize' => true,
                'chart' => false,
            ]),
        ];
    }
}
