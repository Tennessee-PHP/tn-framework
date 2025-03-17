<?php

namespace TN\TN_Core\Attribute;

/**
 * handles one property being automatically derived from another
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Derived
{
    public function __construct(
        public string $source,
        public string $derivationMethod
    )
    {
    }
}