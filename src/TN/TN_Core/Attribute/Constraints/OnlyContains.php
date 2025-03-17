<?php

namespace TN\TN_Core\Attribute\Constraints;
;

/**
 * constraints a string to only contain characters specified in the regex set
 * 
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class OnlyContains extends Constraint {

    /**
     * @param string $regex
     * @param string $readableCharacters
     */
    public function __construct(protected string $regex, protected string $readableCharacters) {}

    /**
     * @param mixed $value
     */
    public function validate(mixed $value): void
    {
        if (preg_match("/^[$this->regex]*$/", $value)) {
            $this->valid = true;
        } else {
            $this->valid = false;
            $this->error = $this->readableName . ' can only contain ' . $this->readableCharacters;
        }
    }
}