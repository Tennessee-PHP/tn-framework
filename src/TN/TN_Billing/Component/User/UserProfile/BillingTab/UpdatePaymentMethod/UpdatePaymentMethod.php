<?php

namespace TN\TN_Billing\Component\User\UserProfile\BillingTab\UpdatePaymentMethod;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;
use TN\TN_Billing\Model\PaymentMethodUpdate;

class UpdatePaymentMethod extends JSON
{
    public function prepare(): void
    {
        $user = User::getActive();

        // Get POST data
        $nonce = $_POST['nonce'] ?? '';
        $deviceData = $_POST['devicedata'] ?? '';
        $processPayment = !empty($_POST['processpayment']);

        // Create payment method update instance
        $paymentMethodUpdate = PaymentMethodUpdate::getFromUser($user);

        // Process the update
        $result = $paymentMethodUpdate->update($nonce, $deviceData, $processPayment);

        // If we got an array back, it contains errors
        if (is_array($result)) {
            throw new ValidationException($result[0]['error']);
        }

        // Success - transaction was processed
        $this->data = [
            'success' => true
        ];
    }
}
