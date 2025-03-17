<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\JSON;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class Price extends Controller
{
    #[Path('staff/sales/change-prices')]
    #[Component(\TN\TN_Billing\Component\Price\EditPrices\EditPrices::class)]
    #[RoleOnly('sales-admin')]
    public function editPrices(): void {}

    #[Path('staff/sales/change-prices/submit')]
    #[RoleOnly('sales-admin')]
    #[Component(\TN\TN_Billing\Component\Price\EditPrices\SavePrices::class)]
    public function savePrices(): void {}
}