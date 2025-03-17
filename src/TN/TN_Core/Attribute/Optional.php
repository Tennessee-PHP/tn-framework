<?php

namespace TN\TN_Core\Attribute;

/**
 * when a property is allowed to be unset in which case validation is not applied
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Optional
{
    public function __construct()
    {
    }
}