<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

enum SearchLogicalOperator: string
{
    case And = 'and';
    case Or = 'or';
}