<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Page
{
    public function __construct(
        public string $title,
        public string $description = '',
        public bool $index = false
    )
    {
    }
}