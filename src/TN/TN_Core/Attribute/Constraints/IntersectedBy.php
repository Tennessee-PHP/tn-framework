<?php

namespace TN\TN_Core\Attribute\Constraints;

/**
 * constraints an array's items to always be included in a separate array
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class IntersectedBy extends Constraint {

    /**
     * @param mixed $arr
     */
    public function __construct(protected mixed $arr) {}

    /**
     * @param mixed $value
     */
    public function validate(mixed $value)
    {
        $intersect = array_intersect($value, $this->arr);
        if (count($intersect) === count($value)) {
            $this->valid = true;
        } else {
            $this->valid = false;
            $this->error = $this->readableName . ' contains invalid items';
        }
    }
}