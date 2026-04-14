<?php

namespace TN\TN_Core\Model\PersistentModel\Storage\MySQL;

enum MySQLSelectType {
    case Objects;
    case Count;
    case CountGrouped;
    case CountAndSum;
    case Sum;
}