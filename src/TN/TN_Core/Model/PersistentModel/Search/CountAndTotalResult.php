<?php

namespace TN\TN_Core\Model\PersistentModel\Search;

class CountAndTotalResult
{
    public function __construct(public int $count, public float $total) {}
}