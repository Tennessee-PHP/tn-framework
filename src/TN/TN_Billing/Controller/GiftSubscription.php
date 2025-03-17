<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class GiftSubscription extends Controller
{
    #[Path('staff/sales/comp')]
    #[Component(\TN\TN_Billing\Component\GiftSubscription\ListGiftSubscriptions\ListGiftSubscriptions::class)]
    #[RoleOnly('sales-admin')]
    public function listGiftSubscriptions(): void {}

    #[Path('staff/sales/submit')]
    #[RoleOnly('sales-admin')]
    #[Component(\TN\TN_Billing\Component\GiftSubscription\ListGiftSubscriptions\AddGiftSubscriptions::class)]
    public function addGiftSubscriptions(): void {}
}
