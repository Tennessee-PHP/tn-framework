<?php

namespace TN\TN_Core\Component\DataSeries\DataSeriesChart;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\DataSeries\DataSeries;

/**
 * renders a data series as a table
 * 
 */
class DataSeriesChart extends HTMLComponent
{
    /** @var DataSeries */
    public DataSeries $dataSeries;

    /** @var string */
    public string $chartType;

    /** @var string */
    public string $labelColumnKey;

    /** @var string[] */
    public array $dataSetColumnKeys;

    /** @var string the config to pass into the chart */
    public string $config;

    public bool $stackBars = false;

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        // calculate the values
        $labels = [];
        $data = [];
        $labelColumn = $this->dataSeries->getColumnByKey($this->labelColumnKey);

        unset($key);
        foreach ($this->dataSetColumnKeys as $key) {
            $column = $this->dataSeries->getColumnByKey($key);
            $data[] = [
                'label' => $column->label,
                'data' => [],
                'column' => $column
            ];
        }

        unset($entry);
        foreach ($this->dataSeries->entries as $entry) {
            $labels[] = $entry->getDisplayValue($labelColumn);
            unset($item);
            foreach ($data as &$item) {
                $item['data'][] = $entry->getValue($item['column']);
            }
        }

        unset($item);
        foreach ($data as &$item) {
            unset($item['column']);
            $item['borderWidth'] = 1;
            if ($this->chartType == 'bar' && $this->stackBars) {
                $item['stack'] = 'main';
            }
        }

        $this->config = json_encode([
            'type' => $this->chartType,
            'data' => [
                'labels' => $labels,
                'datasets' => $data
            ],
            'options' => [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true
                    ]
                ]
            ]
        ]);
    }
}