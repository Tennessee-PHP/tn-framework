<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

use TN\TN_Core\Attribute\MySQL\ForeignKey;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Error\PersistentModel\SearchErrorMessage;
use TN\TN_Core\Error\PersistentModel\SearchException;
use TN\TN_Core\Model\Package\Stack;

class SearchComparisonJoin extends SearchComparison
{
    /**
     * @throws \ReflectionException
     * @throws SearchException
     */
    public function __construct(
        public ?string $joinFromClass = null,
        public ?string $joinToClass = null) {

        $joinFromClass = Stack::resolveClassName($joinFromClass);
        $joinToClass = Stack::resolveClassName($joinToClass);

        $fromReflection = new \ReflectionClass($joinFromClass);
        $primaryKeyProperty = null;
        foreach ($fromReflection->getProperties() as $property) {
            if ($property->getAttributes(PrimaryKey::class)) {
                $primaryKeyProperty = $property;
                break;
            }
        }
        if (!$primaryKeyProperty) {
            throw new SearchException(SearchErrorMessage::FromClassNoPrimaryKey, $joinFromClass);
        }

        $toReflection = new \ReflectionClass($joinFromClass);
        $foreignKeyProperty = null;
        foreach ($toReflection->getProperties() as $property) {
            $attributes = $property->getAttributes(ForeignKey::class);
            if (empty($attributes)) {
                continue;
            }
            $attribute = $attributes[0]->newInstance();
            if (Stack::resolveClassName($attribute->foreignClassName) === $joinToClass) {
                $foreignKeyProperty = $property;
                break;
            }
        }

        if (!$foreignKeyProperty) {
            throw new SearchException(SearchErrorMessage::ToClassNoForeignKey, $joinToClass);
        }

        parent::__construct(
            argument1: new SearchComparisonArgument(property: $primaryKeyProperty->getName(), class: $joinToClass),
            operator: SearchComparisonOperator::Equals,
            argument2: new SearchComparisonArgument(property: $foreignKeyProperty->getName(), class: $joinFromClass)
        );
    }
}