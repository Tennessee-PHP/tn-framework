<?php

namespace TN\TN_Billing\Model;

use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\User\User;

/**
 * utility class to handle an update to a user's payment method
 */
class PaymentMethodUpdate
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\GetUser;
    use \TN\TN_Core\Trait\GetInstanceViaStack;

    protected User $user;

    /**
     * gets an instance for the user
     * @param User $user
     * @return PaymentMethodUpdate
     */
    public static function getFromUser(User $user): PaymentMethodUpdate
    {
        $paymentMethodUpdate = self::getInstance();
        $paymentMethodUpdate->user = $user;
        return $paymentMethodUpdate;
    }

    /**
     * @param string $nonce
     * @param string $deviceData
     * @param bool $processPayment
     * @return bool|array
     */
    public function update(string $nonce, string $deviceData, bool $processPayment): bool|array
    {
        if (empty($deviceData)) {
            return [
                [
                    'error' => 'No device data specified'
                ]
            ];
        }

        if (empty($nonce)) {
            return [
                [
                    'error' => 'No payment details specified'
                ]
            ];
        }

        $subscription = $this->user->getActiveSubscription();
        if (!($subscription instanceof Subscription)) {
            return [
                [
                    'error' => 'Could not find an active subscription to update'
                ]
            ];
        }

        // split on whether we are just trying to check for valid update or if we're charging the card
        if ($processPayment) {
            return $this->processPayment($nonce, $deviceData);
        } else {
            return $this->updateVaulted($nonce, $deviceData);
        }
    }

    /**
     * process a new payment
     * @param string $nonce
     * @param string $deviceData
     * @return bool|array
     */
    protected function processPayment(string $nonce, string $deviceData): bool|array
    {
        // For overdue payments, we still need to use the transaction system
        $subscription = $this->user->getActiveSubscription();
        if ($subscription->hasOverduePayment()) {
            $result = $subscription->recurBilling($nonce, $deviceData);
            // If transaction successful, the payment method should already be updated by Braintree
            if ($result && !is_array($result) && $result->success) {
                // Update our local customer record from Braintree (without using the nonce again)
                $customer = Customer::getFromUser($this->user);
                if ($customer->validateOrRecreateInBraintree()) {
                    $braintree = Gateway::getInstanceByKey('braintree');
                    $braintreeCustomer = $braintree->getApiGateway()->customer()->find($customer->customerId);
                    $customer->updateFromBraintreeCustomer($braintreeCustomer);
                }
                return true;
            }
            // Return error array if transaction failed
            return is_array($result) ? $result : [['error' => $result->errorMsg ?? 'Payment processing failed']];
        }

        // No overdue payment, just update the payment method
        return $this->updatePaymentMethodOnly($nonce);
    }

    /**
     * update a vaulted payment (deprecated, use updatePaymentMethodOnly)
     * @param string $nonce
     * @param string $deviceData
     * @return bool
     * @throws ValidationException
     */
    protected function updateVaulted(string $nonce, string $deviceData): bool
    {
        $subscription = $this->user->getActiveSubscription();
        if ($subscription->hasOverduePayment()) {
            throw new ValidationException('There is a payment due for this subscription that should be processed, ' .
                'instead of only updating the payment details');
        }

        return $this->updatePaymentMethodOnly($nonce);
    }

    /**
     * Update payment method using direct Braintree API call
     * @param string $nonce
     * @return bool
     * @throws ValidationException
     */
    protected function updatePaymentMethodOnly(string $nonce): bool
    {
        // Get or create customer
        $customer = Customer::getFromUser($this->user);

        // Validate customer exists in Braintree, recreate if needed
        if (!$customer->validateOrRecreateInBraintree()) {
            throw new ValidationException('Unable to create or validate customer in Braintree');
        }

        // Update payment method using Braintree API
        $braintree = Gateway::getInstanceByKey('braintree');
        $result = $braintree->getApiGateway()->customer()->update(
            $customer->customerId,
            [
                'paymentMethodNonce' => $nonce
            ]
        );

        if (!$result->success) {
            throw new ValidationException('We were unable to update your payment method: ' . $result->message);
        }

        // Update local customer record
        $customer->updateFromBraintreeCustomer($result->customer);

        // Ensure new payment method is set as default in Braintree
        if (!empty($customer->vaultedToken)) {
            $customer->updateDefaultPaymentMethodToken();
        }

        // Send notification email
        $subscription = $this->user->getActiveSubscription();
        $plan = $subscription->getPlan();

        Email::sendFromTemplate(
            'payment/paymentmethodupdated',
            $this->user->email,
            [
                'nextTransactionTs' => $subscription->nextTransactionTs,
                'username' => $this->user->username,
                'planName' => $plan->name
            ]
        );

        return true;
    }
}
