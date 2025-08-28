<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

/**
 * LoadMore attribute indicates that a component supports infinite scroll/load more functionality
 * 
 * Components with this attribute will:
 * - Have access to 'more', 'from', and 'fromId' parameters for pagination
 * - Support partial template rendering for appending new items
 * - Handle scroll-based loading in TypeScript
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class LoadMore
{
    public function __construct()
    {
    }
}
