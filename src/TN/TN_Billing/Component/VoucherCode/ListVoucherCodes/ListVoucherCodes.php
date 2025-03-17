<?php

namespace TN\TN_Billing\Component\VoucherCode\ListVoucherCodes;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\VoucherCode;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\Time\Time;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Edit Promo Codes', '', false)]
#[Route('TN_Billing:VoucherCode:listVoucherCodes')]
class ListVoucherCodes extends HTMLComponent
{
    public array $codes;
    public int $time;
    public ?Plan $level10;
    public ?Plan $level20;
    public ?Plan $level30;

    public function prepare(): void
    {
        $this->codes = VoucherCode::search(new SearchArguments());
        $this->time = Time::getNow();
        $this->level10 = Plan::getInstanceByKey('level10');
        $this->level20 = Plan::getInstanceByKey('level20');
        $this->level30 = Plan::getInstanceByKey('level30');
    }
}