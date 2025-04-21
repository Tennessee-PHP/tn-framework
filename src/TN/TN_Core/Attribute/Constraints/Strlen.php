<?php

namespace TN\TN_Core\Attribute\Constraints;

/**
 * constraints the length of a string property
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS)]
class Strlen extends Constraint
{

    /**
     * constructor
     * @param int $min
     * @param int $max
     */
    public function __construct(public int $min = 0, public int $max = 999999) {}

    /**
     * validate it!
     * @param mixed $value
     */
    public function validate(mixed $value)
    {
        // If value is a backed enum, get its value
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        $length = strlen($value);
        $this->valid = true;
        $minFail = $length < $this->min;
        $maxFail = $length > $this->max;
        if (!$minFail && !$maxFail) {
            $this->valid = true;
        } else {
            $this->valid = false;
            $this->error = $this->readableName . ' must be ';
            if ($minFail && $maxFail) {
                $this->error .= 'between ' . $this->min . ' and ' . $this->max;
            } else if ($minFail) {
                $this->error .= 'at least ' . $this->min;
            } else {
                $this->error .= 'no more than ' . $this->max;
            }
            $this->error .= ' characters long';
        }
    }
}
