<?php

namespace TN\TN_Core\Component\Input\Select\ProductTypeSelect;

use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

/**
 * select a billing cycle
 * 
 */
class ProductTypeSelect extends Select
{
    public string $htmlClass = 'tn-component-select-producttype-select';
    public string $requestKey = 'producttype';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        $options[] = new Option('subscription', 'Subscriptions', null);
        $options[] = new Option('giftSubscription', 'Gift Subscriptions', null);
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}