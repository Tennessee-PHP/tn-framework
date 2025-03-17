<?php

namespace TN\TN_Reporting\Model\ABTest\ABTestVariant;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Reporting\Model\ABTest\ABTest\ABTest;
use TN\TN_Reporting\Model\ABTest\ABTestDataPoint\ABTestDataPoint;

class ABTestVariant
{
    use ReadOnlyProperties;

    /**
     * @return int number of views
     */
    public int $views = 0;

    /**
     * @var int number of successes
     */
    public int $successes = 0;

    /**
     * @var float conversion percentage
     */
    public float $conversionPercentage = 0.0;

    /**
     * @var bool is this the best option?
     */
    public bool $isBestOption = false;

    /**
     * @var float statistical significance over next best
     */
    public float $statisticalSignificance = 0.0;

    public function __construct(
        /** @var ABTest parent ABTest */
        public ABTest $abTest,

        /** @var string absolute path to the AB Test variant template */
        public string $template,

        /** @var int frequency with which to show this. higher = more. */
        public int    $frequency = 1,

        /** @var bool the winner of a settled A/B test? */
        public bool $winner = false
    )
    {
    }

    /**
     * sets the data on this variant for reporting purposes
     * @param int $views
     * @param int $successes
     * @return void
     */
    public function setData(int $views, int $successes): void
    {
        $this->views = $views;
        $this->successes = $successes;
        $this->conversionPercentage = $successes / $views;
    }

    /**
     * @return void
     */
    public function registerView(): void
    {
        if ($this->ignore()) {
            return;
        }
        $dataPoint = ABTestDataPoint::getInstanceFromKey($this->abTest->key, $this->template);
        $dataPoint->registerView();
    }

    private function ignore(): bool
    {
        $CrawlerDetect = new CrawlerDetect;
        return $CrawlerDetect->isCrawler();
    }

    public function registerSuccess(): void
    {
        if ($this->ignore()) {
            return;
        }
        $dataPoint = ABTestDataPoint::getInstanceFromKey($this->abTest->key, $this->template, false);
        if (!$dataPoint) {
            return;
        }
        $dataPoint->registerSuccess();
    }
}