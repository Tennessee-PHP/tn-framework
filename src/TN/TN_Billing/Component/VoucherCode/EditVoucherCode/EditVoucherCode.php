<?php

namespace TN\TN_Billing\Component\VoucherCode\EditVoucherCode;

use TN\TN_Billing\Component\VoucherCode\ListVoucherCodes\ListVoucherCodes;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\VoucherCode;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Error\ResourceNotFoundException;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Edit Promo Code', 'Add or edit a promo code', false)]
#[Route('TN_Billing:VoucherCode:editVoucherCode')]
#[Breadcrumb(ListVoucherCodes::class)]
class EditVoucherCode extends HTMLComponent
{
    public ?int $id = null;
    public bool $isPhantom;
    public ?VoucherCode $voucher;
    public string|bool|null $deactivate = null;
    public int $totalUses;
    public $totalDiscount;
    public array $plans;
    public array $activePlans;

    public function getPageTitle(): string
    {
        return $this->isPhantom ? 'Add Promo Code' : 'Edit Promo Code';
    }

    public function prepare(): void
    {
        $this->totalUses = 0;
        $this->totalDiscount = 0.00;
        if ($this->id) {
            $this->voucher = VoucherCode::readFromId($this->id);
            if (!$this->voucher) {
                throw new ResourceNotFoundException('Voucher code not found');
            }
            $this->isPhantom = false;
            $startTime = date("Y-m-d", $this->voucher->startTs);
            $endTime = date("Y-m-d", $this->voucher->endTs);
            $this->activePlans = explode(',', $this->voucher->planKeys);
            // todo: implement DataSet::compile
            /*$dataSet = DataSet::compile([
                'TN\Model\Reporting\DayReport\DayVoucherCodeReport' => [
                    'usesByCode',
                    'discount$ByCode'
                ],
            ], $startTime, $endTime, 'day');
            for ($x = 0; $x < count($dataSet->timeUnits); $x++) {
                $d = $dataSet->timeUnits[$x]->data['usesByCode:']->children;
                $keys = array_keys($d);
                foreach ($keys as $key) {
                    if ($key === "usesByCode:" . $voucher->code) {
                        $totalUses += $d[$key]->value;
                    }
                }
            }
            for ($x = 0; $x < count($dataSet->timeUnits); $x++) {
                $d = $dataSet->timeUnits[$x]->data['discount$ByCode:']->children;
                $keys = array_keys($d);
                foreach ($keys as $key) {
                    if ($key === "discount\$ByCode:" . $voucher->code) {
                        $totalDiscount += $d[$key]->value;
                    }
                }
            }
            */
        } else {
            $this->isPhantom = true;
            $this->voucher = VoucherCode::getInstance();
        }

        $this->deactivate = (bool)$this->deactivate;
        $this->plans = Plan::getInstances();
    }
}
