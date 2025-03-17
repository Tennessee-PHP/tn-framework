<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Model\Request\Request;

/**
 * matches a route to a request
 */
abstract class Matcher
{
    public abstract function matches(Request $origin): bool;
}