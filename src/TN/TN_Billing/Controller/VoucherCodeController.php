<?php

namespace TN\TN_Billing\Controller;

use TN\TN_Core\Attribute\Route\JSON;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;

class VoucherCodeController extends Controller
{
    #[Path('staff/sales/voucher-codes/list')]
    #[Component(\TN\TN_Billing\Component\VoucherCode\ListVoucherCodes\ListVoucherCodes::class)]
    #[RoleOnly('marketing-admin')]
    public function listVoucherCodes(): void {}

    #[Path('')]
    #[Path('ststaff/sales/voucher-codes/newaff/sales/voucher-codes/edit/:id')]
    #[Path('staff/sales/voucher-codes/edit/:id/:deactivate')]
    #[Component(\TN\TN_Billing\Component\VoucherCode\EditVoucherCode\EditVoucherCode::class)]
    #[RoleOnly('marketing-admin')]
    public function editVoucherCode(): void {}

    #[Path('staff/sales/voucher-codes/save')]
    #[Component(\TN\TN_Billing\Component\VoucherCode\EditVoucherCode\SaveVoucherCode::class)]
    #[RoleOnly('marketing-admin')]
    public function saveVoucherCode(): void {}
}