<?php

namespace TN\TN_Core\Attribute\Constraints;

/**
 * constraints the length of a string property
 *
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class NumberRange extends Constraint {

    /**
     * constructor
     * @param int $min
     * @param int $max
     */
    public function __construct(private int $min = 0, private int $max = 999999) {}

    /**
     * validate it!
     * @param mixed $value
     */
    public function validate(mixed $value)
    {
        $this->valid = $value >= $this->min && $value <= $this->max;
        if (!$this->valid) {
            $this->error = $this->readableName . ' must be between ' . $this->min . ' and ' . $this->max;
        }
    }
}