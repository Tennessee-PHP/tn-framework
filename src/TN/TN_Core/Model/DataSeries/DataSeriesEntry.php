<?php

namespace TN\TN_Core\Model\DataSeries;

use TN\TN_Core\Model\DataSeries\DataSeriesColumn;

/**
 * a data series entry (a row)
 * 
 */
class DataSeriesEntry
{
    public array $values = [];

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addValue(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * @param DataSeriesColumn $column
     * @return mixed the raw value
     */
    public function getValue(DataSeriesColumn $column): mixed
    {
        return $this->values[$column->key] ?? null;
    }

    /**
     * @param DataSeriesColumn $column
     * @return mixed the column will display the formatted value
     */
    public function getDisplayValue(DataSeriesColumn $column): mixed
    {
        return $column->getDisplayValue($this->getValue($column));
    }
}