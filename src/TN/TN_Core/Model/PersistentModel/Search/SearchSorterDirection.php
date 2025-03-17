<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

enum SearchSorterDirection: int
{
    case ASC = 1;
    case DESC = -1;
}