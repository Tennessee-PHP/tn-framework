<?php

namespace TN\TN_Core\Attribute\UserData;

/**
 * 
 * Clients don't want to know about this property
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ClientIgnore
{
    /**
     * constructor
     */
    public function __construct(
    )
    {
    }
}