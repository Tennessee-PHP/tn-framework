<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class SearchLimit
{
    public function __construct(
        public int $start,
        public int $number) {}
}