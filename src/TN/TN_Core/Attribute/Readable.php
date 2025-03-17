<?php

namespace TN\TN_Core\Attribute;

/**
 * a readable name for a property
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Readable
{
    public function __construct(public string $readable)
    {
    }
}