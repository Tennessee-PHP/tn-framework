<?php

namespace TN\TN_Core\Component\Input\Select\ProductTypeSelect;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;
use TN\TN_Core\Model\Package\Stack;

/**
 * select a reason for a subscription having ended
 * 
 */
class EndedReasonSelect extends Select
{
    public string $htmlClass = 'tn-component-select-endedreason-select';
    public string $requestKey = 'endedreason';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        $subscriptionClass = Stack::resolveClassName(Subscription::class);
        foreach ($subscriptionClass::getEndReasonOptions() as $key => $label) {
            $options[] = new Option($key, $label, null);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}