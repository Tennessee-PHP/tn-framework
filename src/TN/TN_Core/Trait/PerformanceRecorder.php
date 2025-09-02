<?php

namespace TN\TN_Core\Trait;

use TN\TN_Core\Model\Performance\PerformanceEvent;
use TN\TN_Core\Model\Performance\PerformanceLog;

/**
 * Trait to easily add performance recording to I/O classes
 * Provides a single method to track timing of operations
 */
trait PerformanceRecorder
{
    /**
     * Start a performance event for tracking
     * Returns null if not recording (no super-user logged in)
     * 
     * @param string $type Event type (e.g. 'MySQL', 'Redis', 'R2', 'HTTP', 'Email', 'File')
     * @param string $query Description of the operation (SQL query, Redis command, etc.)
     * @param array $metadata Additional context data
     * @return PerformanceEvent|null Event instance to call end() on, or null if not recording
     */
    protected static function startPerformanceEvent(string $type, string $query, array $metadata = []): ?PerformanceEvent
    {
        if (!PerformanceLog::shouldRecord()) {
            return null;
        }

        return PerformanceEvent::start($type, $query, $metadata);
    }
}
