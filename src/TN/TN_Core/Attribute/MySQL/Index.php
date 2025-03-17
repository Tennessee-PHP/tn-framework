<?php

namespace TN\TN_Core\Attribute\MySQL;

/**
 * property is part of the primary key
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Index
{
    public function __construct(public string $indexName)
    {
    }
}