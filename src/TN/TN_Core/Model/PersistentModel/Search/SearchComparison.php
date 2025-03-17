<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchComparison extends SearchCondition
{
    public SearchComparisonArgument $argument1;
    public SearchComparisonOperator $operator;
    public SearchComparisonArgument $argument2;

    public function __construct(mixed $argument1, SearchComparisonOperator|string $operator, mixed $argument2)
    {
        $this->argument1 = $argument1 instanceof SearchComparisonArgument ? $argument1 : SearchComparisonArgument::from($argument1);
        $this->operator = is_string($operator) ? SearchComparisonOperator::from($operator) : $operator;
        $this->argument2 = $argument2 instanceof SearchComparisonArgument ? $argument2 : SearchComparisonArgument::from($argument2);
    }
}