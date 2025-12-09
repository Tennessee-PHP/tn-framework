<?php
declare(strict_types=1);

namespace TN\TN_Core\Attribute;

/**
 * When a property is marked with this attribute, changes to it will not trigger
 * cache invalidation for searches/counts. However, the individual object cache
 * will still be updated to reflect the new value.
 * 
 * Use this for properties like hit counts, view counts, etc. that don't affect
 * search results, sorting, or counting but should still be reflected in cached objects.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class NoCacheInvalidation
{
    public function __construct()
    {
    }
}
