<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchComparisonArgument
{
    public function __construct(
        public ?string $property = null,
        public mixed   $value = null,
        public ?string $class = null) {}

    public static function from(mixed $argument): static
    {
        if (!is_string($argument) || !str_starts_with($argument, '`') || !str_starts_with($argument, '`')) {
            return new static(value: $argument);
        }
        if (str_contains($argument, '.')) {
            $parts = explode('.', $argument);
            return new static(property: substr($parts[1], 1, -1), class: substr($parts[0], 1, -1));
        }
        return new static(property: substr($argument, 1, -1));
    }
}