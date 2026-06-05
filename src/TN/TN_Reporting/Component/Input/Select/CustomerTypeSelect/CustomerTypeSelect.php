<?php

namespace TN\TN_Reporting\Component\Input\Select\CustomerTypeSelect;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

class CustomerTypeSelect extends Select
{
    public string $htmlClass = 'tn-component-select-customer-type-select';
    public string $requestKey = 'customertype';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        foreach (Subscription::getNewSubscriptionCustomerTypeOptions() as $key => $label) {
            $options[] = new Option($key, $label, null);
        }

        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}
