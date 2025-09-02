<?php

namespace TN\TN_Core\Model\Performance;

/**
 * Represents a single performance event (MySQL query, Redis operation, etc.)
 * Value object that tracks timing for individual I/O operations
 */
class PerformanceEvent
{
    public string $type;
    public string $query;
    public float $startTime;
    public ?float $endTime = null;
    public array $metadata;

    /**
     * Create and start a new performance event
     */
    public static function start(string $type, string $query, array $metadata = []): self
    {
        $event = new self();
        $event->type = $type;
        $event->query = $query;
        $event->startTime = microtime(true);
        $event->metadata = $metadata;
        
        return $event;
    }

    /**
     * Mark the event as completed and register it with PerformanceLog
     */
    public function end(): void
    {
        $this->endTime = microtime(true);
        PerformanceLog::registerEvent($this);
    }

    /**
     * Get the duration of this event in seconds
     */
    public function getDuration(): float
    {
        if ($this->endTime === null) {
            return 0.0;
        }
        return $this->endTime - $this->startTime;
    }

    /**
     * Convert event to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'query' => $this->query,
            'duration' => $this->getDuration(),
            'metadata' => $this->metadata
        ];
    }
}
