<?php

namespace TN\TN_Core\Trait;

/**
 *  safely sets private, protected or public property values
 *
 */
trait Setter
{
    /**
     * magic setter
     *
     * @param string $prop
     * @param mixed $value
     * @return void
     */
    public function __set(string $prop, mixed $value): void
    {
        if (property_exists($this, $prop)) {
            $this->$prop = $value;
        }
    }
}