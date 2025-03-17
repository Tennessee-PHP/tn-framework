<?php

namespace TN\TN_Core\Component\Input\Select\TimeUnitSelect;

use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;

/**
 * select a unit of time
 *
 */
class TimeUnitSelect extends Select
{
    public string $htmlClass = 'tn-component-select-timeunit-select';
    public string $requestKey = 'timeunit';

    protected function getOptions(): array
    {
        return [
            new Option('day', 'Day', null),
            new Option('week', 'Week', null),
            new Option('month', 'Month', null),
            new Option('year', 'Year', null)
        ];
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }
}