<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Attribute\Route\RequestMatcher;

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
class Path extends RequestMatcher
{
}