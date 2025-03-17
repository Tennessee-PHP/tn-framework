<?php

namespace TN\TN_Core\Attribute\Constraints;

/**
 * constraints a string to be an email address
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class EmailAddress extends Constraint {

    /**
     * constructor
     */
    public function __construct() {}

    /**
     * @param mixed $value
     */
    public function validate(mixed $value)
    {
        if (\TN\TN_Core\Model\Validation\Validation::email($value)) {
            $this->valid = true;
        } else {
            $this->valid = false;
            $this->error = $this->readableName . ' must be a valid address';
        }
    }
}