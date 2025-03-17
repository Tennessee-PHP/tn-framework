<?php

namespace TN\TN_Core\Component\Input\Select\ProductTypeSelect;

use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Billing\Model\Refund\Refund;

/**
 * select a billing cycle
 *
 */
class RefundReasonSelect extends Select
{
    public string $htmlClass = 'tn-component-select-refundreason-select';
    public string $requestKey = 'refundreason';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        $refundClass = Stack::resolveClassName(Refund::class);
        foreach ($refundClass::getReasonOptions() as $key => $label) {
            $options[] = new Option($key, $label, null);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}