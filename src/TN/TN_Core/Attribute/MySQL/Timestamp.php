<?php

namespace TN\TN_Core\Attribute\MySQL;

/**
 * property is a timestamp
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Timestamp
{
    public function __construct()
    {
    }
}