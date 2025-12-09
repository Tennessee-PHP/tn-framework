<?php

namespace TN\TN_Core\Component\PerformanceModal;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\Route;
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

        // Transform raw metrics into template-friendly format
        $this->metrics = [
            'requestId' => $rawMetrics['requestId'],
            'url' => $rawMetrics['url'],
            'totalTime' => round($rawMetrics['totalTime'], 3),
            'frameworkTime' => round($rawMetrics['frameworkTime'], 3),
            'memoryStart' => $this->formatBytes($rawMetrics['memoryStart']),
            'memoryPeak' => $this->formatBytes($rawMetrics['memoryPeak']),
            'memoryUsed' => $this->formatBytes($rawMetrics['memoryUsed']),
            'totalEvents' => $rawMetrics['totalEvents'],
            'eventsByType' => $this->formatEventsByType($rawMetrics['eventsByType'])
        ];
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Format events by type for template display
     */
    private function formatEventsByType(array $eventsByType): array
    {
        $formatted = [];
        
        foreach ($eventsByType as $type => $data) {
            $formatted[$type] = [
                'count' => $data['count'],
                'totalTime' => round($data['totalTime'], 3),
                'avgTime' => round($data['totalTime'] / max(1, $data['count']), 3),
                'events' => array_map(function($event) {
                    return [
                        'query' => $event['query'],
                        'duration' => round($event['duration'], 3),
                        'metadata' => $event['metadata']
                    ];
                }, $data['events'])
            ];
        }
        
        return $formatted;
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
