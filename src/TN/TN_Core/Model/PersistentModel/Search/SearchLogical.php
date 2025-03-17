<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchLogical extends SearchCondition
{
    public SearchLogicalOperator $operator;
    /** @var SearchCondition[] */ public array $conditions;

    /**
     * @param SearchLogicalOperator $operator
     * @param SearchCondition[] $conditions
     */
    public function __construct(SearchLogicalOperator|string $operator, array $conditions) {
        $this->operator = is_string($operator) ? SearchLogicalOperator::from(strtolower($operator)) : $operator;
        $this->conditions = $conditions;
    }
}