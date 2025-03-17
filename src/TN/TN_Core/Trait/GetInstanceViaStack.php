<?php

namespace TN\TN_Core\Trait;
use TN\TN_Core\Model\Package\Stack;

/**
 * gets an instance of a class, first querying the stack for an extended class
 * @deprecated use Factory trait instead
 */
trait GetInstanceViaStack
{
    /**
     * set the constructor to protected
     */
    protected function __construct()
    {
    }

    /**
     * factory method
     * @return mixed
     */
    public static function getInstance(): mixed
    {
        $className = Stack::resolveClassName(get_called_class());
        return new $className;
    }
}