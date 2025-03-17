<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Request\Request;

/**
 * the path (e.g. address) for a route
 * * @example
 * Let's define a route that matches json/generate/* - the final part of the URL will be populated on the instance
 * JSON\Generate->args['feed'] - so this path variable should equal:
 *
 * <code>
 * 'json/generate/:feed'
 * </code>
 *
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class FileNotFound extends RequestMatcher
{
    /**
     * constructor
     */
    public function __construct()
    {
    }

    /**
     * attempts to match this path against the current request address
     * @param HTTPRequest $request
     * @return bool
     */
    public function matches(Request $origin): bool
    {
        if (!($origin instanceof HTTPRequest)) {
            return false;
        }
        return $origin->notFound;
    }
}