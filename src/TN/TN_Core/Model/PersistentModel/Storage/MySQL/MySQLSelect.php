<?php

namespace TN\TN_Core\Model\PersistentModel\Storage\MySQL;

use ReflectionException;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonArgument;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonOperator;
use TN\TN_Core\Model\PersistentModel\Search\SearchCondition;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorterDirection;

class MySQLSelect
{
    public string $query;
    public array $params;
    public array $foreignTables;

    public function __construct(
        public string          $table,
        public string          $className,
        public MySQLSelectType $selectType,
        public SearchArguments $search,
        public ?string         $sumProperty = null
    )
    {
        $this->className = Stack::resolveClassName($className);
        $this->query = "SELECT ";
        $this->query .= match ($selectType) {
            MySQLSelectType::Objects => "`{$table}`.*",
            MySQLSelectType::Count => "COUNT(*)",
            MySQLSelectType::CountAndSum => "COUNT(*) as count, SUM(`{$table}`.`{$sumProperty}`) as sum",
            MySQLSelectType::Sum => "SUM(`{$table}`.*)"
        };
        $this->query .= " FROM {$table}";

        $this->foreignTables = [];
        foreach ($search->conditions as $condition) {
            $this->getConditionForeignTables($condition);
        }
        $foreignTables = array_unique(array_values($this->foreignTables));
        if (!empty($foreignTables)) {
            $this->query .= ', ' . implode(', ', $foreignTables);
        }

        $this->params = [];

        if (!empty($search->conditions)) {
            $this->query .= ' WHERE ';
            foreach ($search->conditions as $i => $condition) {
                if ($i > 0) {
                    $this->query .= ' AND ';
                }
                $this->addCondition($condition);
            }
        }

        if (!empty($search->sorters)) {
            $this->query .= ' ORDER BY ';
            foreach ($search->sorters as $i => $sorter) {
                if ($i > 0) {
                    $this->query .= ', ';
                }
                $this->addSorter($sorter);
            }
        }

        if ($search->limit) {
            $this->query .= " LIMIT {$search->limit->start}, {$search->limit->number}";
        }
    }

    private function getConditionForeignTables(SearchCondition $condition): void
    {
        if ($condition instanceof SearchComparison) {
            $this->getComparisonForeignTables($condition);
        } else if ($condition instanceof SearchLogical) {
            foreach ($condition->conditions as $subCondition) {
                $this->getConditionForeignTables($subCondition);
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function getComparisonForeignTables(SearchComparison $comparison): void
    {
        $classes = [];
        if ($comparison->argument1->class) {
            $classes[] = $comparison->argument1->class;
        }
        if ($comparison->argument2->class) {
            $classes[] = $comparison->argument2->class;
        }

        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(TableName::class);
            if (empty($attributes)) {
                continue;
            }
            $table = $attributes[0]->newInstance()->name;
            if ($table !== $this->table) {
                $this->foreignTables[$class] = $table;
            }
        }
    }

    private function addSorter(SearchSorter $sorter): void
    {
        $this->query .= "`{$this->table}`.`{$sorter->property}` ";
        $this->query .= match ($sorter->direction) {
            SearchSorterDirection::ASC => 'ASC',
            SearchSorterDirection::DESC => 'DESC'
        };
    }

    private function addCondition(SearchCondition $condition): void
    {
        if ($condition instanceof SearchComparison) {
            $this->addComparison($condition);
        } else if ($condition instanceof SearchLogical) {
            $this->addLogical($condition);
        }
    }

    private function addComparison(SearchComparison $comparison): void
    {
        $this->addComparisonArgument($comparison->argument1);
        $this->addComparisonOperator($comparison->operator);
        $this->addComparisonArgument($comparison->argument2);
    }

    private function addComparisonArgument(SearchComparisonArgument $argument): void
    {
        if ($argument->property) {
            if (!$argument->class || $argument->class === $this->className) {
                $this->query .= "`{$this->table}`";
            } else {
                $this->query .= "`{$this->foreignTables[$argument->class]}`";
            }
            $this->query .= ".`{$argument->property}`";
        } else {
            if (is_array($argument->value)) {
                $this->query .= '(' . implode(', ', array_fill(0, count($argument->value), '?')) . ')';
                $this->params = array_merge($this->params, $argument->value);
            } else {
                $this->query .= '?';
                $this->params[] = $argument->value;
            }
        }
    }

    private function addComparisonOperator(SearchComparisonOperator $operator): void
    {
        $this->query .= ' ' . $operator->value . ' ';
    }

    private function addLogical(SearchLogical $logical): void
    {
        $this->query .= '(';
        foreach ($logical->conditions as $i => $condition) {
            if ($i !== 0) {
                $this->query .= ' ' . $logical->operator->value . ' ';
            }
            $this->addCondition($condition);
        }
        $this->query .= ')';
    }

}