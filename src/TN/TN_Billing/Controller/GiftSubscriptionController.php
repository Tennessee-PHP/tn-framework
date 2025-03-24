<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class GiftSubscriptionController extends Controller
{
    #[Path('staff/sales/comp')]
    #[Component(\TN\TN_Billing\Component\GiftSubscription\ListGiftSubscriptions\ListGiftSubscriptions::class)]
    #[RoleOnly('sales-admin')]
    public function listGiftSubscriptions(): void {}

    #[Path('staff/sales/submit')]
    #[RoleOnly('sales-admin')]
    #[Component(\TN\TN_Billing\Component\GiftSubscription\ListGiftSubscriptions\AddGiftSubscriptions::class)]
    public function addGiftSubscriptions(): void {}

    #[Path('gift/activate/:key')]
    #[Anyone]
    #[Component(\TN\TN_Billing\Component\GiftSubscription\ActivateGiftSubscription\ActivateGiftSubscription::class)]
    public function activate(): void {}

    #[Path('gift/status/:key')]
    #[Anyone]
    #[Component(\TN\TN_Billing\Component\GiftSubscription\GetGiftSubscriptionStatus\GetGiftSubscriptionStatus::class)]
    public function status(): void {}

    #[Path('gift/status/:key/submit')]
    #[Anyone]
    #[Component(\TN\TN_Billing\Component\GiftSubscription\GetGiftSubscriptionStatus\ActionGiftSubscriptionStatus::class)]
    public function submitStatus(): void {}
}
