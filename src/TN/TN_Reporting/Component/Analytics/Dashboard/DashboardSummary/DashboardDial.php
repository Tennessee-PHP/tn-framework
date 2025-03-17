<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Core\Component\HTMLComponent;

class DashboardDial extends HTMLComponent
{
    public string $prefix = '';
    public string $suffix = '';
    public int $decimals = 0;
    public int|float $now;
    public int|float $year;
    public int|float $season;
    public bool $absoluteMath = false;
    public bool $invertGoal = false;

    public ?float $yearPcDiff = null;
    public ?float $seasonPcDiff = null;
    public bool $yearDiffPositive = false;
    public bool $seasonDiffPositive = false;

    public function prepare(): void
    {
        if ($this->absoluteMath) {
            $this->yearPcDiff = $this->now - $this->year;
            $this->seasonPcDiff = $this->now - $this->season;
        } else {
            if ($this->now > 0 && $this->year > 0)
            {
                $this->yearPcDiff = ($this->now - $this->year) / $this->year * 100;
            }
            if ($this->now > 0 && $this->season > 0) {
                $this->seasonPcDiff = ($this->now - $this->season) / $this->season * 100;
            }
        }

        $this->yearDiffPositive = $this->yearPcDiff >= 0;
        $this->seasonDiffPositive = $this->seasonPcDiff >= 0;

        if ($this->invertGoal) {
            $this->yearDiffPositive = !$this->yearDiffPositive;
            $this->seasonDiffPositive = !$this->seasonDiffPositive;
        }
    }
}