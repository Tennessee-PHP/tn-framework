<?php

namespace TN\TN_Billing\Trait;
use TN\TN_Billing\Model\Subscription\Plan\Plan;

/**
 * get related plan
 *
 */
trait GetPlan
{
    /**
     * returns the associated plan
     * @return ?Plan
     */
    public function getPlan(): ?Plan
    {
        return Plan::getInstanceByKey($this->planKey);
    }
}