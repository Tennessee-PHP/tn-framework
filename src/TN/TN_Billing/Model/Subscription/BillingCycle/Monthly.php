<?php

namespace TN\TN_Billing\Model\Subscription\BillingCycle;

/**
 * A monthly billing cycle
 * 
 */
class Monthly extends BillingCycle
{
    protected string $key = 'monthly';
    protected string $name = 'Monthly';
    protected int $numMonths = 1;
    protected int $gracePeriod = 7;
    public function getNextTs(int $ts): int
    {
        return $this->addMonths($ts, 1);
    }
    public function getPreviousTs(int $ts): int
    {
        return $this->removeMonths($ts, 1);
    }
}