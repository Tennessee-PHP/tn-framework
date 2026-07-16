<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\UsersOnly;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Controller\Controller;

class UserProfileController extends Controller
{
    #[Path(':userId/profile/action/billing/update-payment-method')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\User\UserProfile\BillingTab\UpdatePaymentMethod\UpdatePaymentMethod::class)]
    public function updatePaymentMethod(): void {}

    #[Path(':userId/profile/action/billing/save-subscription-voucher-code')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\User\UserProfile\BillingTab\SaveSubscriptionVoucherCode\SaveSubscriptionVoucherCode::class)]
    public function saveSubscriptionVoucherCode(): void {}

    #[Path(':userId/profile/action/billing/save-subscription-next-renewal-complimentary')]
    #[UsersOnly]
    #[Component(\TN\TN_Billing\Component\User\UserProfile\BillingTab\SaveSubscriptionNextRenewalComplimentary\SaveSubscriptionNextRenewalComplimentary::class)]
    public function saveSubscriptionNextRenewalComplimentary(): void {}
}
