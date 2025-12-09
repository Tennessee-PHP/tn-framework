<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

enum SearchComparisonOperator: string
{
    case Equals = '=';
    case NotEquals = '!=';
    case Matches = 'LIKE';
    case NotLike = 'NOT LIKE';
    case GreaterThan = '>';
    case GreaterThanOrEquals = '>=';
    case LessThan = '<';
    case LessThanOrEquals = '<=';
    case In = 'IN';
    case NotIn = 'NOT IN';
    case Is = 'IS';
    case IsNot = 'IS NOT';
    case IsNull = 'IS NULL';
    case IsNotNull = 'IS NOT NULL';
}
