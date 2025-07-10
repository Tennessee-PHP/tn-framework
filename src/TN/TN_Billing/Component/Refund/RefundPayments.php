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
        // Validate user ID
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            throw new ValidationException(['Invalid user ID provided.']);
        }

        $user = User::readFromId((int)$_POST['id']);
        if (!$user instanceof User) {
            throw new ValidationException(['User not found with ID: ' . $_POST['id']]);
        }

        $errors = [];
        $successCount = 0;

        // Check if transactionIds are provided and is an array
        if (!isset($_POST['transactionIds']) || !is_array($_POST['transactionIds'])) {
            throw new ValidationException(['No valid transaction IDs provided for refund.']);
        }

        $transactionIds = $_POST['transactionIds'];
        $reason = $_POST['reason'] ?? 'No reason provided';
        $comment = $_POST['comment'] ?? '';
        $cancelSubscription = isset($_POST['cancel']) && (int)$_POST['cancel'] === 1;

        // Validate that we have at least one transaction ID
        if (empty($transactionIds)) {
            throw new ValidationException(['No transaction IDs selected for refund.']);
        }

        // Iterate through the provided transaction IDs
        foreach ($transactionIds as $transactionId) {
            // Validate transaction ID format
            if (!is_numeric($transactionId)) {
                $errors[] = "Invalid transaction ID format: '{$transactionId}' (must be numeric)";
                continue;
            }

            $transactionId = (int)$transactionId;
            $transaction = Transaction::readFromId($transactionId);

            // Check if transaction exists
            if (!$transaction instanceof Transaction) {
                $errors[] = "Transaction not found: ID {$transactionId}";
                continue;
            }

            // Check if transaction belongs to the specified user
            if ($transaction->userId !== $user->id) {
                $errors[] = "Transaction {$transactionId} does not belong to user {$user->username} (ID: {$user->id})";
                continue;
            }

            // Check if transaction is already refunded
            if ($transaction->refunded) {
                $errors[] = "Transaction {$transactionId} has already been refunded";
                continue;
            }

            // Check if transaction was successful (can't refund failed transactions)
            if (!$transaction->success) {
                $errors[] = "Transaction {$transactionId} was not successful and cannot be refunded (Amount: \${$transaction->amount}, Error: " . ($transaction->errorMsg ?: 'Unknown error') . ")";
                continue;
            }

            // Attempt the refund
            $res = $transaction->refund($reason, $comment, $cancelSubscription);
            if (is_array($res)) {
                // Extract error message from the returned array
                $errorMessage = $res['error'] ?? 'Unknown refund error for transaction ' . $transactionId;
                $errors[] = $errorMessage;
            } else {
                $successCount++;
            }
        }

        // If there were any errors, throw them all
        if (!empty($errors)) {
            if ($successCount > 0) {
                // Some succeeded, some failed
                $errors[] = "Successfully refunded {$successCount} transaction(s), but encountered errors with others.";
            }
            throw new ValidationException($errors);
        }

        $this->data = [
            'result' => 'success',
            'message' => "Successfully refunded {$successCount} payment(s) for {$user->username}"
        ];
    }
}
