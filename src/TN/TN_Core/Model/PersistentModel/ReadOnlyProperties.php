<?php

namespace TN\TN_Core\Model\PersistentModel;

use Exception;

/**
 *  makes all public properties on the class read only
 *
 */
trait ReadOnlyProperties
{
    /**
     * magic setter
     *
     * @param string $prop
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function __set(string $prop, mixed $value): void
    {
        throw new Exception('Property ' . $prop . ' on class ' . static::class . ' is read only');
    }
}