<?php

namespace TN\TN_Billing\Model\Customer\Braintree;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\TNException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User;

/**
 * a braintree customer
 * 
 */
#[TableName('braintree_customers')]
class Customer implements Persistence
{
    use MySQL;
    use PersistentModel;
    use \TN\TN_Core\Trait\GetUser;

    /** @var int  user's id */
    public int $userId;

    /** @var string the braintree id */
    public string $customerId;

    /** @var string the payment method of the customer */
    public string $paymentMethod = '';

    /** @var string card expiration of the customer. empty string if unknown */
    public string $cardExpiration = '';

    /** @var string card type of the customer. empty string if unknown */
    public string $cardType = '';

    /** @var string account name of the customer (e.g. paypal login). empty string if unknown */
    public string $accountName = '';

    /** @var string the vaulted token for recurring payments */
    public string $vaultedToken = '';

    /**
     * gets one without creation; false otherwise
     * @param User $user
     * @return Customer|null
     */
    public static function getExistingFromUser(User $user): ?Customer
    {
        $customers = self::searchByProperty('userId', $user->id);
        return count($customers) > 0 ? $customers[0] : null;
    }

    /**
     * searches and if fails, creates one
     * @param User $user
     * @return Customer|null
     * @throws TNException
     */
    public static function getFromUser(User $user): ?Customer
    {
        if (!isset($user->id)) {
            return null;
        }

        $customers = self::searchByProperty('userId', $user->id);
        if (count($customers) > 0) {
            return $customers[0];
        }

        $braintree = Gateway::getInstanceByKey('braintree');
        $result = $braintree->getApiGateway()->customer()->create([
            'firstName' => $user->first,
            'lastName' => $user->last,
            'email' => $user->email
        ]);

        if (!$result->success) {
            throw new TNException('braintree api failed to create customer');
        }

        $customer = self::getInstance();
        $customer->update([
            'customerId' => $result->customer->id,
            'userId' => $user->id
        ]);

        return $customer;
    }

    public function getReadablePaymentMethod(): string
    {
        $method = strtoupper($this->paymentMethod);
        return match ($method) {
            'ANDROID_PAY_CARD' => 'Google Pay',
            'APPLE_PAY_CARD' => 'Apple Pay',
            'CREDIT_CARD' => 'credit',
            'MASTERPASS_CARD' => 'Masterpass',
            'SAMSUNG_PAY_CARD' => 'Samsung Pay',
            'VISA_CHECKOUT_CARD' => 'Visa',
            'PAYPAL_ACCOUNT' => 'PayPal',
            'VENMO_ACCOUNT' => 'Venmo',
            default => $method
        };
    }

    public function hasVaultedToken(): bool
    {
        return !empty($this->vaultedToken);
    }

    /**
     * Check if customer has a valid vaulted payment method
     * This should be used instead of hasVaultedToken() for determining if "Previous Payment Method" option should be shown
     * @return bool
     */
    public function hasValidVaultedPayment(): bool
    {
        return !empty($this->vaultedToken) && !empty($this->paymentMethod);
    }

    /** just inserted - email the subscriber if it was created active */
    protected function afterSaveInsert(): void
    {
        if (!empty($this->vaultedToken)) {
            $this->updateDefaultPaymentMethodToken();
        }
    }

    /** @param array $changedProperties */
    protected function afterSaveUpdate(array $changedProperties): void
    {
        if (in_array('vaultedToken', $changedProperties) && !empty($this->vaultedToken)) {
            $this->updateDefaultPaymentMethodToken();
        }
    }

    /** @return bool erase the data from braintree */
    public function braintreeErase(): bool
    {
        return true;
    }

    /** @return bool */
    public function updateDefaultPaymentMethodToken(): bool
    {
        $braintree = Gateway::getInstanceByKey('braintree');
        try {
            $updateResult = $braintree->getApiGateway()->paymentMethod()->update(
                $this->vaultedToken,
                [
                    'options' => [
                        'makeDefault' => true
                    ]
                ]
            );
        } catch (\Braintree\Exception\NotFound $e) {
            return false;
        }
        return $updateResult->success;
    }

    /**
     * Validate that this customer exists in Braintree, recreate if not
     * @return bool true if customer is valid, false if recreation failed
     */
    public function validateOrRecreateInBraintree(): bool
    {
        if (empty($this->customerId)) {
            return $this->recreateInBraintree();
        }

        $braintree = Gateway::getInstanceByKey('braintree');
        try {
            $braintree->getApiGateway()->customer()->find($this->customerId);
            return true;
        } catch (\Braintree\Exception\NotFound $e) {
            return $this->recreateInBraintree();
        }
    }

    /**
     * Recreate this customer in Braintree with current user data
     * @return bool true if recreation successful
     */
    public function recreateInBraintree(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $braintree = Gateway::getInstanceByKey('braintree');
        $result = $braintree->getApiGateway()->customer()->create([
            'firstName' => $user->first,
            'lastName' => $user->last,
            'email' => $user->email
        ]);

        if ($result->success) {
            $this->update([
                'customerId' => $result->customer->id,
                'paymentMethod' => '',
                'cardExpiration' => '',
                'cardType' => '',
                'accountName' => '',
                'vaultedToken' => ''
            ]);
            return true;
        }

        return false;
    }

    /**
     * Update local customer record from Braintree customer object
     * @param object $braintreeCustomer The Braintree customer object
     * @return void
     */
    public function updateFromBraintreeCustomer(object $braintreeCustomer): void
    {
        $update = ['customerId' => $braintreeCustomer->id];

        if (!empty($braintreeCustomer->paymentMethods)) {
            // Find the payment method with the most recent createdAt timestamp
            $paymentMethod = null;
            $mostRecentTime = null;

            foreach ($braintreeCustomer->paymentMethods as $method) {
                if (isset($method->createdAt)) {
                    $createdAt = $method->createdAt;
                    if ($createdAt instanceof \DateTime) {
                        $timestamp = $createdAt->getTimestamp();
                    } else {
                        // Handle string timestamps if needed
                        $timestamp = strtotime($createdAt);
                    }

                    if ($mostRecentTime === null || $timestamp > $mostRecentTime) {
                        $mostRecentTime = $timestamp;
                        $paymentMethod = $method;
                    }
                }
            }

            // If no timestamps found, fall back to first payment method
            if (!$paymentMethod) {
                $paymentMethod = $braintreeCustomer->paymentMethods[0];
            }

            // Better payment method type detection
            if (isset($paymentMethod->cardType)) {
                $update['paymentMethod'] = 'CREDIT_CARD';
                $update['cardType'] = $paymentMethod->cardType;
                $update['accountName'] = '';
            } elseif (isset($paymentMethod->email)) {
                $update['paymentMethod'] = 'PAYPAL_ACCOUNT';
                $update['accountName'] = $paymentMethod->email;
                $update['cardType'] = '';
            } else {
                $update['paymentMethod'] = 'UNKNOWN';
            }

            if (isset($paymentMethod->token)) {
                $update['vaultedToken'] = $paymentMethod->token;
            }

            if (isset($paymentMethod->expirationMonth) && isset($paymentMethod->expirationYear)) {
                $update['cardExpiration'] = $paymentMethod->expirationMonth . '/' . $paymentMethod->expirationYear;
            }
        }

        $this->update($update);
    }
}
