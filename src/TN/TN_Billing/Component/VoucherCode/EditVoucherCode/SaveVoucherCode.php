<?php

namespace TN\TN_Billing\Component\VoucherCode\EditVoucherCode;

use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Model\_Deprecated\Util\Arrays;

class SaveVoucherCode extends JSON
{
    public function prepare(): void
    {
        if (isset($_POST['id'])) {
            $voucher = VoucherCode::readFromId($_POST['id']);
        } else {
            $voucher = VoucherCode::getInstance();
        }

        // check to see which plans are active, add those to an array;
        $planKeys = [];
        foreach (Plan::getInstances() as $plan) {
            // we only care about paid plans
            if ($plan->paid) {
                $key = $plan->key;
                // Add the plan key if the checkbox was checked (value matches the key or equals 1)
                if (isset($_POST[$key]) && ($_POST[$key] === $key || $_POST[$key] === '1')) {
                    $planKeys[] = $key;
                }
            }
        }

        $endTs = strtotime($_POST['end']);
        if ($endTs > 0) {
            $endTs = strtotime(date('Y-m-d', $endTs) . ' 23:59:59');
        }

        $voucher->update([
            'name' => Arrays::getIndex($_POST, 'name', 'string', ''),
            'code' => strtoupper(Arrays::getIndex($_POST, 'code', 'string', '')),
            'discountPercentage' => Arrays::getIndex($_POST, 'discount', 'int', ''),
            'numTransactions' => Arrays::getIndex($_POST, 'numTransactions', 'int', 1),
            'startTs' => strtotime($_POST['start']),
            'endTs' => $endTs,
            'planKeys' => implode(',', $planKeys)
        ]);

        $this->data = [
            'result' => 'success',
            'voucherCodeId' => $voucher->id,
            'message' => 'Voucher code saved successfully'
        ];
    }
}
