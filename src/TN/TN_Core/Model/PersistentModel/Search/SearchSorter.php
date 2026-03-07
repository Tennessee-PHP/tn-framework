<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchSorter
{
    public string $property;
    public SearchSorterDirection $direction;

    /** When set, ORDER BY uses this class's table (must be joined via conditions). */
    public ?string $class = null;

    public function __construct(string $property, SearchSorterDirection|string|int $direction, ?string $class = null) {
        $this->property = $property;
        $this->class = $class;
        $this->direction = match ($direction) {
            'desc', 'DESC', SearchSorterDirection::DESC, SORT_DESC => SearchSorterDirection::DESC,
            default => SearchSorterDirection::ASC
        };
    }
}