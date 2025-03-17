<?php

namespace TN\TN_Billing\Model\Gateway;
use TN\TN_Billing\Model\Transaction\GooglePlay\Transaction;

/**
 * 
 */
class GooglePlay extends Gateway
{
    protected string $key = 'googleplay';
    protected string $name = 'Google Play';
    protected bool $mutableSubscriptions = false;
    protected string $transactionClass = Transaction::class;
}