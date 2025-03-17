<?php

namespace TN\TN_Billing\Component\Refund;

use TN\TN_Billing\Model\Transaction\Braintree\Transaction;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

class RefundPayments extends JSON {
    public function prepare(): void
    {
        $user = User::readFromId($_POST['id']);
        $resArray = [];

        // we need to iterate through every index that has been marked for a refund
        for ($i = 0; $i < 100; $i++) {
            if (isset($_POST[$i])) {
                $transaction = Transaction::readFromId($_POST[$i]);
                if ($transaction instanceof Transaction && $transaction->userId === $user->id && !($transaction->refunded))
                {
                    $res = $transaction->refund($_POST['reason'], $_POST['comment'], (int)($_POST['cancel'] ?? 0) === 1);
                    if (is_array($res)) {
                        $resArray[] = $res;
                    }
                }
            }
        }

        if (!empty($resArray)) {
            throw new ValidationException($resArray);
        }

        $this->data = [
            'result' => 'success',
            'message' => 'All payments refunded successfully'
        ];
    }
}