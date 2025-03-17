<?php

namespace TN\TN_Reporting\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class Refund extends Controller
{
    #[Path('staff/sales/refunds')]
    #[Component(\TN\TN_Reporting\Component\Refund\ListRefunds\ListRefunds::class)]
    #[RoleOnly('sales-reporting')]
    public function listRefunds(): void {}
}
