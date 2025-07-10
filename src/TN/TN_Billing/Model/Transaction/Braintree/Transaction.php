<?php

namespace TN\TN_Billing\Model\Transaction\Braintree;

use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\TNException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

/**
 * a braintree transaction
 *
 */
#[TableName('braintree_transactions')]
class Transaction extends \TN\TN_Billing\Model\Transaction\Transaction
{
    use MySQL;

    /** @var string|null braintree's ID */
    public ?string $braintreeId = null;

    /** @var bool if it can be retried again (due to soft decline) */
    public bool $isRetryable = false;

    /** @var string the code that tells us why the payment was declined */
    public string $processorResponseCode = '';

    /** @var string what we sent to braintree */
    public string $encodedRequest;

    /** @var string what braintree sent back to us */
    public string $encodedResponse;

    /** @var string the nonce used to communicate with braintree */
    #[Impersistent]
    public string $nonce;

    /** @var string the requesting IP address */
    public string $ip = '';

    /**
     * gets all transactions of this braintree type
     * @param User $user
     * @return array
     */
    public static function getFromUser(User $user): array
    {
        return self::searchByProperty('userId', $user->id);
    }

    /**
     * get all transactions for a given subscription
     * @param Subscription $subscription
     * @return array
     */
    public static function getFromSubscription(Subscription $subscription): array
    {
        return self::searchByProperty('subscriptionId', $subscription->id);
    }

    /** @return bool|string needs to be public for voiding transactions solely created to update payment details */
    public function actionRefund(): bool|string
    {
        if (defined('UNIT_TESTING') && UNIT_TESTING) {
            return true;
        }

        // need to get the status
        $braintree = Gateway::getInstanceByKey('braintree');
        $transaction = $braintree->getApiGateway()->transaction()->find($this->braintreeId);

        $status = $transaction->status ?? '';

        if (empty($status)) {
            return 'status empty';
        }

        switch (strtoupper($status)) {
            case 'AUTHORIZING':
            case 'AUTHORIZED':
            case 'SUBMITTED_FOR_SETTLEMENT':
            case 'SETTLEMENT_PENDING':
                $res = $this->apiVoid();
                break;
            case 'SETTLED':
            case 'SETTLING':
                $res = $this->apiRefund();
                break;
            // the following don't need refunding
            case 'AUTHORIZATION_EXPIRED':
            case 'PROCESSOR_DECLINED':
            case 'SETTLEMENT_DECLINED':
            case 'FAILED':
            case 'GATEWAY_REJECTED':
            case 'VOIDED':
                return true;

                // can't void these
            default:
                return 'unrecognized status (' . $status . ')';
        }

        if ($res !== true) {
            return $res;
        }

        return true;
    }

    /** @return bool|string put the transaction to void over the braintree api */
    protected function apiVoid(): bool|string
    {
        if (defined('UNIT_TESTING') && UNIT_TESTING) {
            return true;
        }
        $braintree = Gateway::getInstanceByKey('braintree');
        $result = $braintree->getApiGateway()->transaction()->void($this->braintreeId);
        return $result->success ? true : 'Braintree void failed: ' . ($result->message ?? 'Unknown error');
    }

    /** @return bool|string put the transaction to refund over the braintree api */
    protected function apiRefund(): bool|string
    {
        if (defined('UNIT_TESTING') && UNIT_TESTING) {
            return true;
        }
        $braintree = Gateway::getInstanceByKey('braintree');
        $result = $braintree->getApiGateway()->transaction()->refund($this->braintreeId);

        return $result->success ? true : 'Braintree refund failed: ' . ($result->message ?? 'Unknown error');
    }

    protected function treatDescriptor(array $descriptor): array
    {
        if (isset($descriptor['url'])) {
            $url = $descriptor['url'];
            $url = preg_replace('/https?:\/\//i', '', $url);
            $url = preg_replace('/\//i', '', $url);
            $url = preg_replace('/\-/i', '', $url);
            $url = substr($url, 0, 13);
            $descriptor['url'] = $url;
        }
        if (isset($descriptor['name'])) {
            $parts = explode('*', $descriptor['name']);
            $company = $parts[0];
            $product = $parts[1];

            // for some inane reason, the company name must be either exactly 3, 7 or 12 characters.
            // don't ask me why... ask braintree!
            if (strlen($company) >= 12) {
                $company = substr($company, 0, 12);
            } else if (strlen($company) >= 7) {
                $company = substr($company, 0, 7);
            } else {
                $company = substr($company, 0, 3);
                if (strlen($company) < 3) {
                    $company = substr($company . '---', 0, 3);
                }
            }

            // whole thing must be 22 characters or less
            $descriptor['name'] = substr($company . '*' . $product, 0, 22);
        }
        return $descriptor;
    }

    /**
     * @see https://developer.paypal.com/braintree/docs/reference/request/transaction/sale
     * @param array $descriptor
     * @param array $lineItems
     * @param string|false $nonce - if false, rely on braintree customer's vaulted payment info
     * @param string|false $deviceData
     * @param bool $submitForSettlement
     * @throws ValidationException
     * @throws TNException
     */
    public function execute(
        array        $descriptor = [],
        array $lineItems = [],
        string|false $nonce = false,
        string|false $deviceData = false,
        bool $submitForSettlement = true
    ): void {
        $braintree = Gateway::getInstanceByKey('braintree');
        $options = [
            'amount' => round($this->amount, 2),
            'discountAmount' => round($this->discount, 2),
            'purchaseOrderNumber' => $this->id,
            'options' => [
                'submitForSettlement' => $submitForSettlement,
                'storeInVaultOnSuccess' => true
            ]
        ];

        if ($nonce !== false) {
            $options['paymentMethodNonce'] = $nonce;
        }

        $user = $this->getUser();
        if ($user instanceof User) {
            $customer = Customer::getFromUser($user);
            $options['customerId'] = $customer->customerId;
        }

        // device data from the client (all the way from JS!), and use this to set the transaction source also
        if ($deviceData !== false) {
            $options['deviceData'] = $deviceData;
            $options['transactionSource'] = 'recurring_first';
        } else {
            $options['transactionSource'] = 'recurring';
        }

        $options['lineItems'] = $lineItems;
        $options['descriptor'] = $this->treatDescriptor($descriptor);


        $this->update([
            'encodedRequest' => serialize($options),
            'ts' => Time::getNow()
        ]);

        if (defined('UNIT_TESTING') && UNIT_TESTING) {
            $this->update([
                'success' => true
            ]);
            return;
        }

        $result = $braintree->getApiGateway()->transaction()->sale($options);

        $errorMessage = '';
        if (!$result->success && isset($result->transaction)) {
            $t = $result->transaction;
            $errorMessage = $result->message;

            // Add customer details if available
            if (isset($t->customer)) {
                $errorMessage .= sprintf(
                    " | Customer: %s %s (%s)",
                    $t->customer['firstName'] ?? 'Unknown',
                    $t->customer['lastName'] ?? 'Unknown',
                    $t->customer['email'] ?? 'No email'
                );
            }

            // Add payment method details if available
            if (isset($t->creditCard)) {
                $errorMessage .= sprintf(
                    " | Card: %s ending in %s (exp: %s/%s)",
                    $t->creditCard['cardType'] ?? 'Unknown',
                    $t->creditCard['last4'] ?? 'xxxx',
                    $t->creditCard['expirationMonth'] ?? 'xx',
                    $t->creditCard['expirationYear'] ?? 'xxxx'
                );
            }
        }

        $this->update([
            'encodedResponse' => $result->success ? 'success' : ($errorMessage ?: ($result->message ?? 'Unknown error'))
        ]);

        if (!$result->transaction) {
            $this->onFailure();
            $this->update([
                'success' => false,
                'errorMsg' => 'We were unable to attempt to process your payment: ' . $result->message
            ]);
            // nothing more we can do - nothing else to process!
            return;
        }

        $transaction = $result->transaction;
        //$responseType = $transaction->processorResponseType ?? '';
        if ($result->success) {
            $this->approved($transaction);
        } else {
            $this->declined($transaction);
        }
    }

    /**
     * may wish to override this inside a package higher up the stack e.g. for monitoring
     */
    protected function onFailure(): void {}

    /**
     * get the card details!
     * @param object|null $details
     * @return array
     */
    protected function cardFromTransactionDetails(object|null $details): array
    {
        if (!$details) {
            return [
                'cardExpiration' => '',
                'cardType' => '',
                'vaultedToken' => ''
            ];
        }
        return [
            'cardExpiration' => ($details->expirationMonth ?? '??') . '/' . ($details->expirationYear ?? '??'),
            'cardType' => $details->cardType ?? ($details->sourceCardType ?? ''),
            'vaultedToken' => $details->token ?? ''
        ];
    }

    /** @param object $transaction transaction with braintree was approved
     * @throws ValidationException
     */
    protected function approved(object $transaction): void
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->transactionSuccessful($this, $this->getProduct());
            $customer = Customer::getFromUser($user);
            $update = [
                'paymentMethod' => strtoupper($transaction->paymentInstrumentType)
            ];
            switch (strtoupper($transaction->paymentInstrumentType)) {
                case 'ANDROID_PAY_CARD':
                    $update = array_merge($update, $this->cardFromTransactionDetails($transaction->googlePayCardDetails));
                    break;
                case 'APPLE_PAY_CARD':
                    $update = array_merge($update, $this->cardFromTransactionDetails($transaction->applePayCardDetails));
                    break;
                case 'CREDIT_CARD':
                    $update = array_merge($update, $this->cardFromTransactionDetails($transaction->creditCardDetails));
                    break;
                case 'MASTERPASS_CARD':
                    $update = array_merge($update, $this->cardFromTransactionDetails($transaction->masterpassCardDetails));
                    break;
                case 'SAMSUNG_PAY_CARD':
                    $update = array_merge($update, $this->cardFromTransactionDetails($transaction->samsungPayCardDetails));
                    break;
                case 'VISA_CHECKOUT_CARD':
                    $update = array_merge($update, $this->cardFromTransactionDetails($transaction->visaCheckoutCardDetails));
                    break;
                case 'PAYPAL_ACCOUNT':
                    $update['accountName'] = $transaction->paypalDetails->payerEmail ?? 'unknown paypal account';
                    break;
                case 'VENMO_ACCOUNT':
                    $update['accountName'] = $transaction->venmoAccountDetails->username ?? 'unknown venmo account';
                    break;
            };
            $customer->update($update);
        }

        $this->update([
            'success' => true,
            'braintreeId' => $transaction->id ?? ''
        ]);
    }

    /**
     * transaction with braintree was declined
     * @param object $transaction
     * @return void
     * @throws ValidationException
     */
    protected function declined(object $transaction): void
    {
        $responseCode = $transaction->processorSettlementResponseCode ?? '';
        $responseType = $transaction->processorResponseType ?? '';
        $status = $transaction->status ?? '';

        $update = [
            'braintreeId' => $transaction->id ?? ''
        ];
        switch ($responseType) {
            case 'soft_declined':
                $update['isRetryable'] = true;
                $update['success'] = false;
                break;
            case 'hard_declined':
                $update['isRetryable'] = false;
                $update['success'] = false;
                break;
        }

        switch (strtoupper($status)) {
            case 'AUTHORIZATION_EXPIRED':
                /*
                 * Braintree:
                 * The transaction spent too much time in the Authorized status and was marked as expired.
                 * Expiration time frames differ by payment instrument subtype, transaction type, and, in some
                 * cases, merchant category.
                 *
                 * TN:
                 * this doesn't make sense as the transaction should have already submitted for settlement
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 1).';
                break;
            case 'AUTHORIZED':
                /*
                 * Braintree:
                 * The processor authorized the transaction. Your customer may see a pending charge on his or her
                 * account. However, before the customer is actually charged and before you receive the funds, you
                 * must submit the transaction for settlement. If you do not want to settle the transaction, you
                 * should void it to avoid a misuse of authorization fee.
                 *
                 * TN:
                 * this doesn't make sense as the transaction should have already submitted for settlement
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 2).';
                break;
            case 'AUTHORIZING':
                /*
                 * Braintree:
                 * Braintree does not offer anything on what this means.
                 *
                 * TN:
                 * can't really do anything here except log the unexpected error.
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 3).';
                break;
            case 'SETTLEMENT_PENDING':
                /*
                 * Braintree:
                 * The transaction has not yet fully settled. This status is rare, and it does not always indicate
                 * a problem with settlement. Only certain types of transactions can be affected.
                 *
                 * TN:
                 * can't really do anything here except log the unexpected error.
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 4).';
                break;
            case 'PROCESSOR_DECLINED':
                /*
                 * Braintree:
                 * The processor declined the transaction. The processor response code has information about why
                 * the transaction was declined.
                 *
                 * TN:
                 * let's handle this the same as settlement_declined as both need to look at processor response code
                 */
            case 'SETTLEMENT_DECLINED':
                /*
                 * Braintree:
                 * The processor declined to settle the sale or refund request, and the result is unsuccessful.
                 * This can happen for a number of reasons, but the processor settlement response code may have more
                 * information about why the transaction was declined. This status is rare, and only certain types
                 * of transactions can be affected.
                 *
                 * TN:
                 * Approval was passed, but settlement wasn't! show generic message (from braintree) to show to
                 * prevent fraud (if we tell them why, it helps them to fix it!)
                 */
                $update['processorResponseCode'] = $responseCode;
                $update['errorMsg'] = 'There was a problem processing your credit card; please double check your payment information and try again.';
                break;
            case 'FAILED':
                /*
                 * Braintree:
                 * An error occurred when sending the transaction to the processor.
                 *
                 * TN:
                 * got to report exactly that I guess!
                 */
                $update['errorMsg'] = 'There was an issue processing the payment. Please try again!';
                break;
            case 'GATEWAY_REJECTED':
                /*
                 * Braintree:
                 * The gateway rejected the transaction because AVS, CVV, duplicate, risk threshold, or premium
                 * fraud checks failed, or because you have reached the processing limit on your provisional
                 * merchant account.
                 *
                 * TN:
                 * this one also shouldn't happen surely.
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 5).';
                break;
            case 'SETTLED':
                /*
                 * Braintree:
                 * The transaction has been settled.
                 *
                 * TN:
                 * again this should not be the case as we're only in this switch loop in the event of failure.
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 6).';
                break;
            case 'SETTLING':
                /*
                 * Braintree:
                 * The transaction is in the process of being settled. This is a transitory state. A transaction
                 * can't be voided once it reaches Settling status, but can be refunded.
                 *
                 * TN:
                 * again this should not be the case as we're only in this switch loop in the event of failure.
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 7).';
                break;
            case 'SUBMITTED_FOR_SETTLEMENT':
                /*
                 * Braintree:
                 * The transaction has been submitted for settlement and will be included in the next settlement
                 * batch. Settlement happens nightly – the exact time depends on the processor.
                 *
                 * TN:
                 * again this should not be the case as we're only in this switch loop in the event of failure.
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 8).';
                break;
            case 'VOIDED':
                /*
                 * Braintree:
                 * The transaction was voided. You can void transactions when the status is Authorized, Submitted
                 * for Settlement, or – in the case of certain PayPal transactions – Settlement Pending.
                 *
                 * TN:
                 * should never be here - the transaction should only ever be voided if WE did that!
                 */
                $update['errorMsg'] = 'An unexpected error occurred with your payment (code: 9).';
                break;
        }

        $this->update($update);
    }

    protected function maybeEndSubscriptionAfterRefund(): void
    {
        if ($this->isLatestTransactionInSubscription()) {
            $subscription = Subscription::readFromId($this->subscriptionId);
            $subscription->end('refunded', true);
        }
    }

    /**
     * attempt to refund
     * @param string $reason
     * @param string $comment
     * @param bool $cancelSubscription
     * @return Refund|array
     * @throws ValidationException
     */
    public function refund(string $reason = '', string $comment = '', bool $cancelSubscription = true): Refund|array
    {
        if (!$this->success) {
            return [
                'error' => 'Cannot refund an unsuccessful transaction (Transaction ID: ' . $this->id . ', Status: ' . ($this->success ? 'success' : 'failed') . ')'
            ];
        }

        if ($this->refunded) {
            $this->maybeEndSubscriptionAfterRefund();
            return [
                'error' => 'Transaction ID ' . $this->id . ' has already been refunded'
            ];
        }

        $res = $this->actionRefund();
        if ($res !== true) {
            return [
                'error' => 'The credit transaction failed to complete for Transaction ID ' . $this->id . ' (Braintree ID: ' . ($this->braintreeId ?? 'N/A') . '). Reason: ' . $res
            ];
        }

        if ($cancelSubscription) {
            $this->maybeEndSubscriptionAfterRefund();
        }

        $refund = Refund::getInstance();
        $refundData = [
            'ts' => Time::getNow(),
            'userId' => $this->userId,
            'transactionId' => $this->id,
            'transactionClass' => __CLASS__,
            'amount' => $this->amount,
            'reason' => $reason,
            'comment' => $comment
        ];

        try {
            $refund->update($refundData);
        } catch (ValidationException $e) {
            $errorDetails = [];
            $errorDetails[] = 'Transaction ID: ' . $this->id;
            $errorDetails[] = 'Braintree ID: ' . ($this->braintreeId ?? 'N/A');
            $errorDetails[] = 'User ID: ' . $this->userId;
            $errorDetails[] = 'Amount: $' . number_format($this->amount, 2);
            $errorDetails[] = 'Validation errors: ' . implode(', ', $e->errors);

            return [
                'error' => 'Failed to create refund record. ' . implode(' | ', $errorDetails)
            ];
        } catch (\Exception $e) {
            $errorDetails = [];
            $errorDetails[] = 'Transaction ID: ' . $this->id;
            $errorDetails[] = 'Braintree ID: ' . ($this->braintreeId ?? 'N/A');
            $errorDetails[] = 'User ID: ' . $this->userId;
            $errorDetails[] = 'Amount: $' . number_format($this->amount, 2);
            $errorDetails[] = 'Database error: ' . $e->getMessage();

            return [
                'error' => 'Failed to create refund record. ' . implode(' | ', $errorDetails)
            ];
        }

        $this->update([
            'refunded' => true
        ]);

        $user = $this->getUser();
        if ($user instanceof User) {
            Email::sendFromTemplate(
                'billing/transaction/refund',
                $user->email,
                [
                    'amount' => $this->amount,
                    'originalTs' => $this->ts,
                    'username' => $user->username
                ]
            );
        }

        return $refund;
    }

    /**
     * @inheritDoc
     */
    public function getFee(): float
    {
        return ($this->amount * 0.0275); // aggregate of 2.75% as per May 2022 braintree invoice
    }
}
