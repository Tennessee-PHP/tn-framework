<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;

#[TableName('analytics_subscriptions_ended_entries')]
class SubscriptionsEndedEntry extends \TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsTypeEntry
{
    public static string $type = 'ended';

    /** @var array|string[] */
    public static array $filters = ['gateway', 'plan', 'billingCycle', 'endedReason'];

    public ?string $endedReasonKey = null;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating subscription ' . get_called_class()::$type . ' report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey, $this->endedReasonKey]) . PHP_EOL;
        $result = Subscription::countAndTotalByType(get_called_class()::$type, $this->dayTs, strtotime('+1 day', $this->dayTs), $this->planKey ?? '', $this->billingCycleKey ?? '', $this->gatewayKey ?? '', $this->endedReasonKey);
        $this->update([
            'count' => $result->count
        ]);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];
        $values['gatewayKey'] = [''];
        foreach (Gateway::getInstances() as $gateway) {
            $values['gatewayKey'][] = $gateway->key;
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

        $values['endedReasonKey'] = [''];
        foreach (Subscription::getEndReasonOptions() as $key => $label) {
            $values['endedReasonKey'][] = $key;
        }
        return $values;
    }
}
