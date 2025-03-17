<?php

namespace TN\TN_Core\Model\DataSeries;

/**
 * a data series column
 *
 */
class DataSeriesColumn
{
    /** @var string column key */
    public string $key;

    /** @var string readable label */
    public string $label;

    /** @var string|null  */
    public ?string $prefix = null;

    /** @var string|null  */
    public ?string $suffix = null;

    /** @var int|null will be fed into number_format along with $decimalSeparator and $thousandsSeperator */
    public ?int $decimals = null;

    /** @var string  */
    public string $decimalSeparator = '.';

    /** @var string  */
    public string $thousandsSeparator = ',';

    /** @var bool emphasize this column */
    public bool $emphasize = false;

    /**
     * @param string $key
     * @param string $label
     * @param array $options
     */
    public function __construct(string $key, string $label, array $options = [])
    {
        $this->key = $key;
        $this->label = $label;

        foreach ($options as $prop => $value) {
            if (property_exists($this, $prop)) {
                $this->$prop = $value;
            }
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function getDisplayValue(mixed $value): string
    {
        if ($this->decimals !== null) {
            $value = number_format($value, $this->decimals, $this->decimalSeparator, $this->thousandsSeparator);
        } else {
            $value = (string)$value;
        }
        return ($this->prefix ?? '') . $value . ($this->suffix ?? '');
    }
}