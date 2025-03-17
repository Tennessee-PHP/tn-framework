<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\Cache as CacheAttribute;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\Time\Time;

/**
 * gets an instance of a class, first querying the stack for an extended class
 *
 */
trait Factory
{
    /**
     * set the constructor to protected
     */
    protected function __construct(array $data)
    {
        foreach ($data as $property => $value) {
            $this->$property = $this->loadPropertyValue($property, $value);
        }
    }

    /**
     * factory method
     * @param array|null $data
     * @return mixed
     */
    public static function getInstance(?array $data = null): static
    {
        $className = Stack::resolveClassName(static::class);
        return new $className($data ?? []);
    }
}