<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchSorter
{
    public string $property;
    public SearchSorterDirection $direction;

    public function __construct(string $property, SearchSorterDirection|string|int $direction) {
        $this->property = $property;
        $this->direction = match ($direction) {
            'desc', 'DESC', SearchSorterDirection::DESC, SORT_DESC => SearchSorterDirection::DESC,
            default => SearchSorterDirection::ASC
        };
    }
}