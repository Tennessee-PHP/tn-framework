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
            $updateResult = $braintree->getApiGateway()->customer()->update(
                $this->customerId,
                [
                    'defaultPaymentMethodToken' => $this->vaultedToken
                ]
            );
        } catch (\Braintree\Exception\NotFound $e) {
            return false;
        }
        return $updateResult->success;
    }
}
