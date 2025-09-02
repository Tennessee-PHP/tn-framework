<?php

namespace TN\TN_Core\Model\Performance;

use TN\TN_Core\Model\User\User;

/**
 * In-memory singleton that collects performance events for the current request
 * Only active when a super-user is logged in
 */
class PerformanceLog
{
    private static ?self $instance = null;
    
    private string $requestId;
    private float $startTime;
    private ?float $endTime = null;
    private string $url;
    private ?int $userId;
    private int $memoryStart;
    private array $events = [];

    private function __construct()
    {
        $this->requestId = uniqid('perf_', true);
        $this->startTime = microtime(true);
        $this->url = $_SERVER['REQUEST_URI'] ?? '';
        $this->userId = User::getActive()->id ?? null;
        $this->memoryStart = memory_get_usage(true);
    }

    /**
     * Get singleton instance - only creates if super-user is logged in
     */
    public static function getInstance(): ?self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (!self::shouldRecord()) {
            return null;
        }

        self::$instance = new self();
        return self::$instance;
    }

    /**
     * Check if we should record performance data (super-user logged in)
     */
    public static function shouldRecord(): bool
    {
        // Prevent infinite recursion - if we're already checking, return false
        static $checking = false;
        if ($checking) {
            return false;
        }
        
        try {
            $checking = true;
            $user = User::getActive();
            $result = $user->loggedIn && $user->hasRole('super-user');
            $checking = false;
            return $result;
        } catch (\Exception $e) {
            $checking = false;
            return false;
        }
    }

    /**
     * Start request timing
     */
    public static function startRequest(): void
    {
        // Just ensure instance is created if needed
        self::getInstance();
    }

    /**
     * End request timing
     */
    public static function endRequest(): void
    {
        $instance = self::getInstance();
        if ($instance) {
            $instance->endTime = microtime(true);
        }
    }

    /**
     * Register a completed performance event
     */
    public static function registerEvent(PerformanceEvent $event): void
    {
        $instance = self::getInstance();
        if ($instance) {
            $instance->events[] = $event;
        }
    }

    /**
     * Get all performance metrics for display
     */
    public function getMetrics(): array
    {
        $totalTime = $this->endTime ? ($this->endTime - $this->startTime) : 0.0;
        $memoryPeak = memory_get_peak_usage(true);
        
        // Group events by type
        $eventsByType = [];
        $totalEventTime = 0.0;
        
        foreach ($this->events as $event) {
            $type = $event->type;
            if (!isset($eventsByType[$type])) {
                $eventsByType[$type] = [
                    'count' => 0,
                    'totalTime' => 0.0,
                    'events' => []
                ];
            }
            
            $eventsByType[$type]['count']++;
            $eventsByType[$type]['totalTime'] += $event->getDuration();
            $eventsByType[$type]['events'][] = $event->toArray();
            $totalEventTime += $event->getDuration();
        }

        return [
            'requestId' => $this->requestId,
            'url' => $this->url,
            'userId' => $this->userId,
            'totalTime' => $totalTime,
            'frameworkTime' => max(0.0, $totalTime - $totalEventTime),
            'memoryStart' => $this->memoryStart,
            'memoryPeak' => $memoryPeak,
            'memoryUsed' => $memoryPeak - $this->memoryStart,
            'eventsByType' => $eventsByType,
            'totalEvents' => count($this->events)
        ];
    }

    /**
     * Get current instance for components to display
     */
    public static function getCurrentMetrics(): ?array
    {
        $instance = self::getInstance();
        return $instance ? $instance->getMetrics() : null;
    }
}
