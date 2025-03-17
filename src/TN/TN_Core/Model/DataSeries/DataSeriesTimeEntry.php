<?php

namespace TN\TN_Core\Model\DataSeries;

use TN\TN_Core\Model\DataSeries\DataSeriesEntry;

class DataSeriesTimeEntry extends DataSeriesEntry
{
    public string $timeUnit;
    public int $startTs;
    public int $endTs;

    public function __construct(string $timeUnit, int $startTs, int $endTs)
    {
        // set the properties from the parameters
        $this->timeUnit = $timeUnit;
        $this->startTs = $startTs;
        $this->endTs = $endTs;
    }

    public function getLabel(): string
    {
        return match($this->timeUnit) {
            'day' => date('M j', $this->startTs),
            'week' => date('M j', $this->startTs) . ' - ' . date('M j', $this->endTs),
            'month' => date('M', $this->startTs),
            'year' => date('Y', $this->startTs),
            default => '',
        };
    }
}