<?php

namespace TN\TN_Core\Attribute\MySQL;

/**
 * when a property should auto-increment in a mysql DB
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AutoIncrement
{
    public function __construct()
    {
    }
}