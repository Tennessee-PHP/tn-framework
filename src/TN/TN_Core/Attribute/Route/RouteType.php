<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Component\Renderer\Renderer;

#[\Attribute(\Attribute::TARGET_METHOD)]
abstract class RouteType
{
    abstract public function getRenderer(array $args = []): ?Renderer;
} 