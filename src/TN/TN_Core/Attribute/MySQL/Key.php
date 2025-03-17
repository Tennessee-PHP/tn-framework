<?php

namespace TN\TN_Core\Attribute\MySQL;

/**
 * sets a class' database table name
 *
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Key {

    /**
     * constructor
     * @param array $properties
     * @param bool $unique
     */
    public function __construct(public array $properties, public bool $unique = false) {}
}