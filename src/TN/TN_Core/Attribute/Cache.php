<?php

namespace TN\TN_Core\Attribute;

use TN\TN_Core\Model\Time\Time;

/**
 * when a property is not saved
 * 
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Cache
{
    public function __construct(public string $version = '1.0', public int $lifespan = Time::ONE_DAY)
    {
    }
}