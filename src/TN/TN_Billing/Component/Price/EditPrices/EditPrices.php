<?php

namespace TN\TN_Billing\Component\Price\EditPrices;

use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Edit Plan Prices', '', false)]
#[Route('TN_Billing:Price:editPrices')]
#[Breadcrumb('Price')]
class EditPrices extends HTMLComponent
{
    public array $plans;
    public array $billingCycles;

    public function prepare(): void
    {
        $this->plans = Plan::getInstances();
        $this->billingCycles = BillingCycle::getInstances();
    }
}