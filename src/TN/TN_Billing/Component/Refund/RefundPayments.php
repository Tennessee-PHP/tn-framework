<?php

namespace TN\TN_Billing\Component\Refund;

use TN\TN_Billing\Model\Transaction\Braintree\Transaction;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

class RefundPayments extends JSON
{
    public function prepare(): void
    {
        $user = User::readFromId($_POST['id']);
        $resArray = [];

        // Check if transactionIds are provided and is an array
        if (!isset($_POST['transactionIds']) || !is_array($_POST['transactionIds'])) {
            throw new ValidationException(['No valid transaction IDs provided for refund.']);
        }

        $transactionIds = $_POST['transactionIds'];
        $reason = $_POST['reason'] ?? 'No reason provided'; // Provide default if not set
        $comment = $_POST['comment'] ?? ''; // Provide default if not set
        $cancelSubscription = isset($_POST['cancel']) && (int)$_POST['cancel'] === 1;

        // Iterate through the provided transaction IDs
        foreach ($transactionIds as $transactionId) {
            $transaction = Transaction::readFromId((int)$transactionId);
            if ($transaction instanceof Transaction && $transaction->userId === $user->id && !$transaction->refunded) {
                $res = $transaction->refund($reason, $comment, $cancelSubscription);
                if (is_array($res)) {
                    // Extract error message from the returned array
                    $errorMessage = $res['error'] ?? 'Unknown refund error';
                    $resArray[] = $errorMessage;
                }
            } else {
                $resArray[] = "Invalid or already refunded transaction ID: {$transactionId}";
            }
        }

        if (!empty($resArray)) {
            throw new ValidationException($resArray);
        }

        $this->data = [
            'result' => 'success',
            'message' => 'Selected payments refunded successfully'
        ];
    }
}
