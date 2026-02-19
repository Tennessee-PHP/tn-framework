<?php

namespace TN\TN_Core\Attribute\Route;

/**
 * CORS: reflect the request Origin header in Access-Control-Allow-Origin.
 * Use for API routes that replace fbg-cloud-server-node (e.g. native/Cordova clients).
 * Replaces #[AllowOrigin] on those routes; use with #[AllowCredentials].
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ReflectOrigin
{
    /**
     * constructor
     */
    public function __construct() {}
}
