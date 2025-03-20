<?php

namespace TN\TN_Core\Component\Input\Select\PlanSelect;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

/**
 * select a subscription plan
 * 
 */
class PlanSelect extends Select
{
    public string $htmlClass = 'tn-component-select-plan-select';
    public string $requestKey = 'plan';
    public bool $paidOnly;

    public function __construct(bool $paidOnly = true)
    {
        parent::__construct();
        $this->paidOnly = $paidOnly;
    }

    protected function getOptions(): array
    {
        $options = [];
        $options[] = new Option('', 'All', null, true);
        foreach (Plan::getInstances() as $plan) {
            if (!$this->paidOnly || $plan->paid) {
                $options[] = new Option($plan->key, $plan->name, $plan);
            }
        }
        return $options;
    }
}
