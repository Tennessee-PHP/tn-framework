<?php

namespace TN\TN_Core\Component\Input\Select\BillingCycleSelect;

use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

/**
 * select a billing cycle
 */
class BillingCycleSelect extends Select
{
    public string $htmlClass = 'tn-component-select-billing-cycle-select';
    public string $requestKey = 'billingCycle';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        foreach (BillingCycle::getEnabledInstances() as $billingCycle) {
            $options[] = new Option($billingCycle->key, $billingCycle->name, $billingCycle);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}
