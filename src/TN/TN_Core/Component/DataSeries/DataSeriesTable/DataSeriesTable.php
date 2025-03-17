<?php

namespace TN\TN_Core\Component\DataSeries\DataSeriesTable;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\DataSeries\DataSeries;

class DataSeriesTable extends HTMLComponent
{
    /** @var DataSeries */
    public DataSeries $dataSeries;

    public function __construct(DataSeries $dataSeries)
    {
        parent::__construct();
        $this->dataSeries = $dataSeries;
    }

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
    }
}