<?php

namespace TN\TN_Billing\Model\Gateway;
use TN\TN_Billing\Model\Transaction\Apple\Transaction;

/**
 *
 */
class Apple extends Gateway
{
    protected string $key = 'apple';
    protected string $name = 'Apple App Store';
    protected bool $mutableSubscriptions = false;
    protected string $transactionClass = Transaction::class;
}