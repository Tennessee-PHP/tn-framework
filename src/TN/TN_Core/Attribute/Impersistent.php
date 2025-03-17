<?php

namespace TN\TN_Core\Attribute;

/**
 * when a property is not saved
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Impersistent
{
    public function __construct()
    {
    }
}