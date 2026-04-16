<?php

namespace TN\TN_Core\Model\Performance;

/**
 * Shapes raw PerformanceLog metrics into the same display-oriented structure as PerformanceModal
 * (rounded seconds, human-readable byte strings, per-type averages, per-event query/duration/metadata)
 * for JSON API responses and classic page rendering.
 */
final class PerformanceDisplayMetrics
{
    /**
     * @param array<string, mixed> $rawMetrics Output of PerformanceLog::getMetrics()
     * @return array<string, mixed>
     */
    public static function fromRaw(array $rawMetrics): array
    {
        return [
            'requestId' => $rawMetrics['requestId'],
            'url' => $rawMetrics['url'],
            'totalTime' => round($rawMetrics['totalTime'], 3),
            'frameworkTime' => round($rawMetrics['frameworkTime'], 3),
            'memoryStart' => self::formatBytes((int) $rawMetrics['memoryStart']),
            'memoryPeak' => self::formatBytes((int) $rawMetrics['memoryPeak']),
            'memoryUsed' => self::formatBytes((int) $rawMetrics['memoryUsed']),
            'totalEvents' => $rawMetrics['totalEvents'],
            'eventsByType' => self::formatEventsByType($rawMetrics['eventsByType']),
        ];
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * @param array<string, mixed> $eventsByType
     * @return array<string, mixed>
     */
    private static function formatEventsByType(array $eventsByType): array
    {
        $formatted = [];

        foreach ($eventsByType as $type => $data) {
            $formatted[$type] = [
                'count' => $data['count'],
                'totalTime' => round($data['totalTime'], 3),
                'avgTime' => round($data['totalTime'] / max(1, $data['count']), 3),
                'events' => array_map(static function ($event) {
                    return [
                        'query' => $event['query'],
                        'duration' => round($event['duration'], 3),
                        'metadata' => $event['metadata'],
                    ];
                }, $data['events']),
            ];
        }

        return $formatted;
    }
}
