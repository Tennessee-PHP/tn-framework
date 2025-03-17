<?php

namespace TN\TN_Billing\Model\Gateway;

/**
 * 
 */
class Free extends Gateway
{
    protected string $key = 'free';
    protected string $name = 'Free';
    protected bool $mutableSubscriptions = true;
}