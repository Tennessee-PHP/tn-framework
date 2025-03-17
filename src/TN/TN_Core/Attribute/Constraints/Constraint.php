<?php

namespace TN\TN_Core\Attribute\Constraints;

use TN\TN_Core\Model\Package\Stack;

/**
 * a validation constraint
 *
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
abstract class Constraint
{

    use \TN\TN_Core\Trait\Getter;

    /** @var string the readable name of the property */
    protected string $readableName;

    /** @var bool did the value pass the constraint? */
    protected bool $valid;

    /** @var string if the value did not pass the constraint, what was the error? */
    protected string $error;

    /** @param mixed $value do the validation! */
    abstract public function validate(mixed $value);

    /**
     * link the constraint to the class
     * @param string $readableName
     * @param string $class
     */
    public function linkToClass(string $readableName, string $class): void
    {
        $this->readableName = $readableName;
        if (property_exists($this, 'arr') && is_string($this->arr)) {
            $this->setArrValue(Stack::resolveClassName($class));
        }
    }

    /** @param string $class convert a string to an array that can be used for validation */
    protected function setArrValue(string $class): void
    {
        $argument = (string)$this->arr;
        $inflectionClass = new \ReflectionClass(__CLASS__);
        if (str_starts_with($argument, '$')) {
            $argument = substr($argument, 1);
            if (str_ends_with($argument, '|keys')) {
                $argument = substr($argument, 0, strlen($argument) - 5);
                $this->arr = array_keys($inflectionClass->getStaticPropertyValue($argument));
            } else {
                $this->arr = $inflectionClass->getStaticPropertyValue($argument);
            }
        } else if (str_starts_with($argument, '()')) {
            $argument = substr($argument, 2);
            if (str_ends_with($argument, '|keys')) {
                $argument = substr($argument, 0, strlen($argument) - 5);
                $this->arr = array_keys($class::$argument());
            } else {
                $this->arr = $class::$argument();
            }
        }
    }
}