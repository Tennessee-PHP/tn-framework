<?php

namespace TN\TN_Core\Debug;

/**
 * Cursor/agent instrumentation: write a single NDJSON line to .debug/ in the project root (TN_ROOT).
 * This is the only allowed way to add PHP debug logs in projects using tn-framework (e.g. fbgsite).
 * Optional log filename allows separate files per issue so logs don't get mixed when debugging multiple things.
 * See fbgsite/docs/debug.md for format, reading logs, and best practices.
 */
class AgentLog
{
    public static function log(
        string $message,
        array $data = [],
        ?string $hypothesisId = null,
        ?string $location = null,
        ?string $logFile = null
    ): void {
        if (!isset($_ENV['TN_ROOT'])) {
            return;
        }
        if ($location === null) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1] ?? null;
            $location = $caller
                ? ($caller['file'] ?? '') . ':' . ($caller['line'] ?? 0)
                : '';
        }
        $payload = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => $hypothesisId ?? '',
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => time() * 1000,
        ];
        $dir = rtrim($_ENV['TN_ROOT'], '/') . '/.debug';
        $filename = $logFile !== null && $logFile !== '' ? basename($logFile) : 'debug.log';
        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            $filename = 'debug.log';
        }
        $path = $dir . '/' . $filename;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents(
            $path,
            json_encode($payload) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
