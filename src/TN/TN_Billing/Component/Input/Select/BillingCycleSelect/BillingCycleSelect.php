<?php

namespace TN\TN_Billing\Component\Input\Select\BillingCycleSelect;

use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

/**
 * select a billing cycle
 * 
 */
class BillingCycleSelect extends Select
{
    public string $htmlClass = 'tn-component-select-billingcycle-select';
    public string $requestKey = 'billingcycle';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        foreach (BillingCycle::getInstances() as $cycle) {
            if ($cycle->enabled) {
                $options[] = new Option($cycle->key, $cycle->name, $cycle);
            }
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}