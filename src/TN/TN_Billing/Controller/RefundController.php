<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Billing\Model\Cart as CartModel;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Command\TimeLimit;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Component;

class RefundController extends Controller
{
    #[Path('staff/users/user/:userId/plans/refund')]
    #[RoleOnly('sales-admin')]
    #[Component(\TN\TN_Billing\Component\Refund\RefundPayments::class)]
    public function refundPayments(): void {}
}
