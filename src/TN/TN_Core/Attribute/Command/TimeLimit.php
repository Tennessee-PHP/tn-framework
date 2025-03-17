<?php

namespace TN\TN_Core\Attribute\Command;

#[\Attribute(\Attribute::TARGET_METHOD)]
class TimeLimit
{
    /**
     * constructor
     * @param int $timeLimit
     */
    public function __construct(
        public int $timeLimit
    )
    {
    }
}