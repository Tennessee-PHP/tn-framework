<?php

namespace TN\TN_Billing\Trait;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;

/**
 * get related billing cycle
 *
 */
trait GetBillingCycle
{
    /**
     * returns the associated billing cycle
     * @return ?BillingCycle
     */
    public function getBillingCycle(): ?BillingCycle
    {
        return BillingCycle::getInstanceByKey($this->billingCycleKey);
    }
}