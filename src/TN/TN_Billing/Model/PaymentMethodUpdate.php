<?php

namespace TN\TN_Billing\Model;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Braintree\Transaction;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\IP\IP;
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
     * @return Transaction|array
     */
    public function update(string $nonce, string $deviceData, bool $processPayment): Transaction|array
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
     * @return Transaction|array
     */
    protected function processPayment(string $nonce, string $deviceData): Transaction|array
    {
        // total piggy back off recur billing!
        $subscription = $this->user->getActiveSubscription();
        if (!$subscription->hasOverduePayment()) {
            return [
                [
                    'error' => 'There is no payment due for this subscription'
                ]
            ];
        }
        return $subscription->recurBilling($nonce, $deviceData);
    }

    /**
     * update a vaulted payment
     * @param string $nonce
     * @param string $deviceData
     * @return Transaction
     * @throws ValidationException
     */
    protected function updateVaulted(string $nonce, string $deviceData): Transaction
    {
        // the transaction itself will do most of the work here! we just need to void the transaction
        // immediately after
        $subscription = $this->user->getActiveSubscription();
        if ($subscription->hasOverduePayment()) {
            throw new ValidationException('There is a payment due for this subscription that should be processed, ' .
                'instead of only updating the payment details');
        }

        $transaction = Transaction::getInstance();
        $transaction->update([
            'userId' => $this->user->id,
            'amount' => 1.00,
            'voucherCodeId' => 0,
            'discount' => 0,
            'ip' => IP::getAddress()
        ]);

        // execute transaction
        $plan = $subscription->getPlan();
        $billingCycle = $subscription->getBillingCycle();
        $transaction->execute(
            [
                'name' => $_ENV['SITE_NAME'] . '*' . $plan->name,
                'url' => $_ENV['BASE_URL']
            ],
            [
                [
                    'description' => $plan->description,
                    'discountAmount' => 0,
                    'kind' => 'debit',
                    'name' => $plan->name . ' ' . $billingCycle->name,
                    'quantity' => 1,
                    'unitAmount' => 1.00,
                    'totalAmount' => 1.00
                ]
            ], $nonce, $deviceData, true);

        if (!$transaction->success) {
            throw new ValidationException($transaction->errorMsg ?? 'Unknown error occurred');
        }

        Email::sendFromTemplate(
            'payment/paymentmethodupdated',
            $this->user->email,
            [
                'nextTransactionTs' => $subscription->nextTransactionTs,
                'username' => $this->user->username,
                'planName' => $plan->name
            ]
        );

        $transaction->actionRefund();
        return $transaction;

    }

}