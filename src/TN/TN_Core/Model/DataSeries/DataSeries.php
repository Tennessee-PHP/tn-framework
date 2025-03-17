<?php

namespace TN\TN_Core\Model\DataSeries;

use TN\TN_Core\Model\DataSeries\DataSeriesColumn;
use TN\TN_Core\Model\DataSeries\DataSeriesEntry;

class DataSeries
{
    /** @var DataSeriesColumn[] */
    public array $columns;

    /** @var DataSeriesEntry[] */
    public array $entries;

    public function __construct(array $columns, array $entries)
    {
        $this->columns = $columns;
        $this->entries = $entries;
    }

    public function getColumnByKey(string $key): ?DataSeriesColumn
    {
        foreach ($this->columns as $column) {
            if ($column->key === $key) {
                return $column;
            }
        }
        return null;
    }
}