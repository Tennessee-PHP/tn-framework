<?php

namespace TN\TN_Core\Attribute\Constraints;

/**
 * constraints values such that they can be validly passed into php's strtotime function
 * @link https://www.php.net/manual/en/function.strtotime
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class StrToTimeable extends Constraint {

    /**
     * constructor
     */
    public function __construct() {}

    /**
     * validate it!
     * @param mixed $value
     */
    public function validate(mixed $value)
    {
        if (strtotime($value) !== false) {
            $this->valid = true;
        } else {
            $this->valid = false;
            $this->error = $this->readableName . ' must be a valid date';
        }
    }
}