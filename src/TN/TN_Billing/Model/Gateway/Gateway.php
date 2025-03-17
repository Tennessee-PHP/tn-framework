<?php

namespace TN\TN_Billing\Model\Gateway;

/**
 * a payment gateway. Every subscription or purchase must be made through a payment gateway, e.g. Braintree
 *
 * @see \TN\TN_Billing\Model\Subscription\Subscription
 * 
 */
abstract class Gateway
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string non-db identifier */
    protected string $key;

    /** @var string  */
    protected string $name;

    /** @var bool whether we should be able to change the dates on subscriptions */
    protected bool $mutableSubscriptions;

    /** @var string  */
    protected string $transactionClass;

}
