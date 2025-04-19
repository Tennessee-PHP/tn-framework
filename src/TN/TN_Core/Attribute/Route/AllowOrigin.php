<?php

namespace TN\TN_Core\Attribute\Route;

/**
 * signifies that a route is an API route.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class AllowOrigin
{
    /**
     * constructor
     */
    public function __construct() {}
}
