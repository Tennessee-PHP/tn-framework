<?php

namespace TN\TN_Core\Component\PerformanceModal;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Model\Performance\PerformanceDisplayMetrics;
use TN\TN_Core\Model\Performance\PerformanceLog;

/**
 * Performance monitoring modal - detailed metrics display
 * Uses slide-out drawer from right side
 */
#[Route('TN_Core:PerformanceController:modal')]
class PerformanceModal extends HTMLComponent
{
    /**
     * Performance metrics from current request
     */
    public array $metrics = [];

    public function prepare(): void
    {
        // Get actual performance metrics from PerformanceLog
        $rawMetrics = PerformanceLog::getCurrentMetrics();
        
        if (!$rawMetrics) {
            $this->metrics = $this->getEmptyMetrics();
            return;
        }

        $this->metrics = PerformanceDisplayMetrics::fromRaw($rawMetrics);
    }

    /**
     * Return empty metrics structure when no data available
     */
    private function getEmptyMetrics(): array
    {
        return [
            'requestId' => 'N/A',
            'url' => 'N/A',
            'totalTime' => 0.0,
            'frameworkTime' => 0.0,
            'memoryStart' => '0 B',
            'memoryPeak' => '0 B',
            'memoryUsed' => '0 B',
            'totalEvents' => 0,
            'eventsByType' => []
        ];
    }
}
