<?php

namespace TN\TN_Reporting\Model\Analytics\DataSeries;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Component\Input\Select\TimeCompareSelect\TimeCompareSelect;
use TN\TN_Core\Model\DataSeries\DataSeriesColumn;
use TN\TN_Core\Model\Package\Stack;

class AnalyticsDataSeriesColumn extends DataSeriesColumn
{
    public bool $chart = false;

    public function adjustLabelForPrefix(string $prefix, array $comparisons, ?string $breakdown): void
    {
        $comparisonStr = '';
        $breakdownStr = '';
        foreach (explode(':', $prefix) as $part) {
            if (isset($comparisons[$part])) {
                $comparisonStr = ' (' . TimeCompareSelect::getReadable($part) . ')';
            } else {
                // it's the breakdown!
                if ($part === 'all') {
                    continue;
                }
                switch ($breakdown) {
                    case 'gatewayKey':
                        $breakdownStr = Gateway::getInstanceByKey($part)->name;
                        break;
                    case 'planKey':
                        $breakdownStr = Plan::getInstanceByKey($part)->name;
                        break;
                    case 'billingCycleKey':
                        $breakdownStr = BillingCycle::getInstanceByKey($part)->name;
                        break;
                    case 'productTypeKey':
                        $breakdownStr = $part === 'subscription' ? 'Subscriptions' : 'Gift Subscriptions';
                        break;
                    case 'refundReasonKey':
                        $refundClass = Stack::resolveClassName(Refund::class);
                        foreach ($refundClass::getReasonOptions() as $key => $label) {
                            if (str_contains($prefix, $key)) {
                                $breakdownStr = $label;
                                break;
                            }
                        }
                        break;
                    case 'endedReasonKey':
                        $subscriptionClass = Stack::resolveClassName(Subscription::class);
                        foreach ($subscriptionClass::getEndReasonOptions() as $key => $label) {
                            if (str_contains($prefix, $key)) {
                                $breakdownStr = $label;
                                break;
                            }
                        }
                        break;
                }
            }
        }

        $this->label = $this->label . (!empty($breakdownStr) ? ': ' . $breakdownStr : '') . $comparisonStr;


    }
}