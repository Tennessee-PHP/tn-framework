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
     * Total number of I/O operations (DB + Redis + R2 + HTTP + Email + File)
     */
    public int $totalOperations = 0;

    /**
     * Number of database queries
     */
    public int $dbQueries = 0;

    /**
     * Number of Redis cache operations
     */
    public int $cacheOperations = 0;

    /**
     * Always true since this component is only instantiated for super-users by Page.php
     */
    public bool $isSuperUser = true;

    public function prepare(): void
    {
        // Get actual render time and operation counts from performance log
        $metrics = PerformanceLog::getCurrentMetrics();
        if ($metrics) {
            $this->renderTime = round($metrics['totalTime'], 3);
            $this->totalOperations = $metrics['totalEvents'];
            
            // Extract specific operation counts
            $eventsByType = $metrics['eventsByType'] ?? [];
            $this->dbQueries = $eventsByType['MySQL']['count'] ?? 0;
            $this->cacheOperations = $eventsByType['Redis']['count'] ?? 0;
        }
    }
}
