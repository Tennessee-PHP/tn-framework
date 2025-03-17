<?php

namespace TN\TN_Core\Attribute\Route;

/**
 * this will use a matches public static function match on the class itself to try to do the match
 *
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class DynamicPath
{
}