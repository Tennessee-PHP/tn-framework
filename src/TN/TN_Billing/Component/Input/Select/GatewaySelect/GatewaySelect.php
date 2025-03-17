<?php

namespace TN\TN_Billing\Component\Input\Select\GatewaySelect;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

/**
 * select a payment gateway
 * 
 */
class GatewaySelect extends Select
{
    public string $htmlClass = 'tn-component-select-gateway-select';
    public string $requestKey = 'gateway';

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        foreach (Gateway::getInstances() as $gateway) {
            $options[] = new Option($gateway->key, $gateway->name, $gateway);
        }
        return $options;
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}