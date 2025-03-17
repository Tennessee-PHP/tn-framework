<?php

namespace TN\TN_Core\Attribute\Command;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Schedule
{
    /**
     * constructor
     * @param string $schedule
     */
    public function __construct(
        public string $schedule
    )
    {
    }
}