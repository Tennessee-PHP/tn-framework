<?php

namespace TN\TN_Billing\Component\User\UserProfile\BillingTab\UpdatePaymentMethod;

use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;
use TN\TN_Billing\Model\PaymentMethodUpdate;

#[Route('TN_Billing:UserProfile:updatePaymentMethod')]
class UpdatePaymentMethod extends JSON
{
    public int $userId;

    public function prepare(): void
    {
        // Handle "me" resolution like other profile components
        if ((string)$this->userId === 'me') {
            $this->userId = User::getActive()->id;
        }

        $observer = User::getActive();

        // Super users can update any user's payment method, others can only update their own
        if ($observer->hasRole('super-user')) {
            $user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`id`', '=', $this->userId)));
        } else {
            // Non-super users can only update their own payment method
            if ($this->userId !== $observer->id) {
                throw new ValidationException('Access denied');
            }
            $user = $observer;
        }

        if (!$user) {
            throw new ValidationException('User not found');
        }

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

        // Success - payment method was updated (result is boolean true)
        $this->data = [
            'success' => true
        ];
    }
}
