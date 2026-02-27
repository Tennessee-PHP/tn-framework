<?php

namespace TN\TN_Core\Attribute\MySQL;

/**
 * Override the default SQL column type for a property (e.g. mediumtext for long string content).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ColumnType
{
    public function __construct(public string $type)
    {
    }
}
