<?php

namespace TN\TN_Core\Attribute\Relationships;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ChildrenClass
{
    /**
     * constructor
     * @param string $class
     */
    public function __construct(
        public string $class
    )
    {
    }
}