<?php

namespace TN\TN_Billing\Model\Gateway;
use Braintree\Gateway as BraintreeAPIGateway;
use TN\TN_Billing\Model\Customer\Braintree\Customer;
use TN\TN_Billing\Model\Transaction\Braintree\Transaction;
use TN\TN_Core\Model\User\User;

/**
 * 
 */
class Braintree extends Gateway
{
    const JS_VERSION = '3.85.2';
    const JS_FILES = [
        'client',
        'hosted-fields',
        'apple-pay',
        'data-collector',
        'paypal-checkout'
    ];

    protected string $key = 'braintree';
    protected string $name = 'Braintree';
    protected bool $mutableSubscriptions = true;

    protected string $transactionClass = Transaction::class;

    /** @var BraintreeAPIGateway the instance of the Braintree API's gateway (NOT the same thing as the TN gateway!!) */
    protected BraintreeAPIGateway $apiGateway;

    /** @return BraintreeAPIGateway singleton getter for the braintree api gateway */
    public function getApiGateway(): BraintreeAPIGateway
    {
        if (!isset($this->apiGateway)) {
            $this->apiGateway = new BraintreeAPIGateway([
                'environment' => $_ENV['BRAINTREE_ENVIRONMENT'],
                'merchantId' => $_ENV['BRAINTREE_MERCHANT_ID'],
                'publicKey' => $_ENV['BRAINTREE_PUBLIC_KEY'],
                'privateKey' => $_ENV['BRAINTREE_PRIVATE_KEY']
            ]);
        }
        return $this->apiGateway;
    }

    /**
     * gets a client token for the specific user (they may have a customer ID already with braintree?)
     * @param User|null $user
     * @return string
     */
    public function generateClientToken(?User $user = null): string
    {
        $options = [];
        $customer = null;
        if ($user) {
            $customer = Customer::getFromUser($user);
            $options['customerId'] = $customer->customerId;
        }

        try {
            return $this->getApiGateway()->clientToken()->generate($options);
        } catch (\InvalidArgumentException $e) {
            if ($customer instanceof Customer) {
                $customer->erase();
            }
            return $this->getApiGateway()->clientToken()->generate([]);
        }

    }

    public function getJsUrls(): array
    {
        $files = [];
        foreach (self::JS_FILES as $file) {
            $files[] = 'https://js.braintreegateway.com/web/' . self::JS_VERSION . '/js/' . $file . '.min.js';
        }
        return $files;
    }

}