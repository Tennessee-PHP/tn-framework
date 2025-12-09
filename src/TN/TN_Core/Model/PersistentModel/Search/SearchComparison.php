<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

/**
 * SearchComparison represents a comparison condition in a database query.
 * 
 * @example Basic usage:
 * new SearchComparison('`username`', '=', 'john')  // username = 'john'
 * new SearchComparison('`age`', '>', 18)           // age > 18
 * new SearchComparison('`createdAt`', '<', '2024-01-01 00:00:00')  // createdAt < '2024-01-01 00:00:00'
 * 
 * @note Column names must be wrapped in backticks (`) to be treated as column references.
 *       Without backticks, they will be treated as string values.
 * 
 * @example Column references vs values:
 * new SearchComparison('`columnName`', '=', 'value')  // CORRECT: columnName = 'value'
 * new SearchComparison('columnName', '=', 'value')    // WRONG: 'columnName' = 'value'
 */
class SearchComparison extends SearchCondition
{
    public SearchComparisonArgument $argument1;
    public SearchComparisonOperator $operator;
    public SearchComparisonArgument $argument2;

    /**
     * 🚨 CRITICAL: Column names MUST be wrapped in backticks!
     * 
     * @param mixed $argument1 Column name (MUST be wrapped in backticks like '`columnName`') or SearchComparisonArgument
     * @param SearchComparisonOperator|string $operator Comparison operator ('=', '<', '>', '!=', 'LIKE', 'IN', etc.)
     * @param mixed $argument2 Value to compare against or SearchComparisonArgument
     * 
     * 🚨 COMMON MISTAKE - Always use backticks for column names:
     * 
     * ✅ CORRECT:
     * new SearchComparison('`username`', '=', 'john')        // username = 'john'
     * new SearchComparison('`screenshotId`', 'IN', [1,2,3])  // screenshotId IN (1,2,3)
     * new SearchComparison('`age`', '>', 18)                 // age > 18
     * 
     * ❌ WRONG (generates invalid SQL):
     * new SearchComparison('username', '=', 'john')          // 'username' = 'john'
     * new SearchComparison('screenshotId', 'IN', [1,2,3])    // ? IN (1,2,3) [BROKEN]
     * new SearchComparison('age', '>', 18)                   // 'age' > 18
     * 
     * Without backticks, column names are treated as string literals!
     */
    public function __construct(mixed $argument1, SearchComparisonOperator|string $operator, mixed $argument2)
    {
        $this->argument1 = $argument1 instanceof SearchComparisonArgument ? $argument1 : SearchComparisonArgument::from($argument1);
        $this->operator = is_string($operator) ? SearchComparisonOperator::from($operator) : $operator;
        $this->argument2 = $argument2 instanceof SearchComparisonArgument ? $argument2 : SearchComparisonArgument::from($argument2);
    }
}