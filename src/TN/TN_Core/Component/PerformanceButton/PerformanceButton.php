<?php

namespace TN\TN_Core\Component\PerformanceButton;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Model\Performance\PerformanceLog;

/**
 * Performance monitoring button - floating bottom-right
 * Shows render time and opens performance modal for super-users
 */
#[Route('TN_Core:PerformanceController:button')]
class PerformanceButton extends HTMLComponent
{
    /**
     * Current request render time in seconds
     */
    public float $renderTime = 0.0;

    /**
     * Always true since this component is only instantiated for super-users by Page.php
     */
    public bool $isSuperUser = true;

    public function prepare(): void
    {
        // Get actual render time from performance log
        $metrics = PerformanceLog::getCurrentMetrics();
        if ($metrics) {
            $this->renderTime = round($metrics['totalTime'], 3);
        }
    }
}
