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

        // Handle null values
        if ($value === null) {
            $value = '';
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
            $this->error .= ' (value ' . $this->valueSnippetForError($value) . ', length ' . $length . ')';
        }
    }

    /**
     * Short, log-safe representation of the value that failed validation.
     */
    private function valueSnippetForError(mixed $value): string
    {
        if (!is_string($value)) {
            $value = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
        }
        $max = 120;
        if (strlen($value) > $max) {
            $value = substr($value, 0, $max - 3) . '...';
        }
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        return $encoded !== false ? $encoded : '"[unprintable]"';
    }
}
