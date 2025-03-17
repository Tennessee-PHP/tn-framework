<?php

namespace TN\TN_Core\Attribute\Components;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Route
{
    public function __construct(
        public string $route
    )
    {
    }
} 