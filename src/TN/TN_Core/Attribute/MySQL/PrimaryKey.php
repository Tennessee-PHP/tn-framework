<?php

namespace TN\TN_Core\Attribute\MySQL;

/**
 * property is part of the primary key
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class PrimaryKey
{
    public function __construct()
    {
    }
}