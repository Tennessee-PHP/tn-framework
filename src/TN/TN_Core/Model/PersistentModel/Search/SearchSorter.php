<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchSorter
{
    public string $property;
    public SearchSorterDirection $direction;

    /** When set, ORDER BY uses this class's table (must be joined via conditions). */
    public ?string $class = null;

    /**
     * When set, ORDER BY uses this SQL fragment for the primary row (must reference only that table).
     * Used when DISTINCT Object selects need ORDER BY on an expression (e.g. COALESCE columns).
     * MySQLSelect emits a matching `_order_{index}` alias in the SELECT list.
     */
    public ?string $orderBySqlExpression = null;

    public function __construct(
        string $property,
        SearchSorterDirection|string|int $direction,
        ?string $class = null,
        ?string $orderBySqlExpression = null,
    ) {
        $this->property = $property;
        $this->class = $class;
        $this->orderBySqlExpression = $orderBySqlExpression;
        $this->direction = match ($direction) {
            'desc', 'DESC', SearchSorterDirection::DESC, SORT_DESC => SearchSorterDirection::DESC,
            default => SearchSorterDirection::ASC
        };
    }
}