<?php

namespace TN\TN_Core\Error\PersistentModel;

enum SearchErrorMessage: string {
    case FromClassNoPrimaryKey = "joinFromClass has no primary key";
    case ToClassNoForeignKey = "joinToClass has no foreign key for joinFromClass";
}