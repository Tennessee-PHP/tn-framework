<?php

namespace TN\TN_Core\Attribute\Constraints;

/**
 * constraints a value to be included in another array
 *
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Inclusion extends Constraint {

    /**
     * constructor
     * @param mixed $arr
     */
    public function __construct(protected mixed $arr) {}

    /**
     * validate it
     * @param mixed $value
     */
    public function validate(mixed $value)
    {
        if (in_array($value, $this->arr)) {
            $this->valid = true;
        } else {
            $this->valid = false;
            $this->error = $this->readableName . ' is not included in the list of valid options';
        }
    }
}