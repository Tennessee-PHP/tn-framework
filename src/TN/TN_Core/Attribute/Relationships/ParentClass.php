<?php

namespace TN\TN_Core\Attribute\Relationships;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ParentClass
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