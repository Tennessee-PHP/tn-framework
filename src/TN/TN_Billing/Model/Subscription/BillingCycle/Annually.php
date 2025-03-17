<?php

namespace TN\TN_Billing\Model\Subscription\BillingCycle;

/**
 * A monthly billing cycle
 * 
 */
class Annually extends BillingCycle
{
    protected string $key = 'annually';
    protected string $name = 'Yearly';
    protected int $numMonths = 12;
    protected int $notifyUpcomingTransactionWithinDays = 15;
    public function getNextTs(int $ts): int
    {
        return $this->addMonths($ts, 12);
    }
    public function getPreviousTs(int $ts): int
    {
        return $this->removeMonths($ts, 12);
    }
}