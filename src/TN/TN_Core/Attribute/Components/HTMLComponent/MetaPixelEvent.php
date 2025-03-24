<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class MetaPixelEvent
{
    public function __construct(public string $event) {}
}
