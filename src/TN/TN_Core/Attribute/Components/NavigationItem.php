<?php

namespace TN\TN_Core\Attribute\Components;

use Attribute;

/**
 * Marks a component as corresponding to a specific navigation item
 * Used by the Page renderer to determine which navigation item should be highlighted
 */
#[Attribute(Attribute::TARGET_CLASS)]
class NavigationItem
{
    public function __construct(
        public readonly string $navigationKey
    ) {}
}
