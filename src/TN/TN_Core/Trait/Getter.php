<?php

namespace TN\TN_Core\Trait;

/**
 *  safely returns private, protected or public property values
 * 
 */
trait Getter
{
    /**
     * magic getter
     *
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop): mixed
    {
        return (property_exists($this, $prop) && isset($this->$prop)) ? $this->$prop : null;
    }
}