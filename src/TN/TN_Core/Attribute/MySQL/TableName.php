<?php

namespace TN\TN_Core\Attribute\MySQL;

/**
 * sets a class' database table name
 *
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TableName {

    /**
     * constructor
     * @param string $name
     */
    public function __construct(public string $name) {}
}