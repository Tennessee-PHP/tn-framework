<?php

namespace TN\TN_Core\Test;

/**
 * Manages PHP state reset between functional tests
 * 
 * The framework assumes traditional PHP request lifecycle (load → process → die),
 * but functional tests run multiple "requests" in the same PHP process.
 * This class provides centralized state clearing to prevent test interference.
 */
class StateManager
{
    /**
     * Reset all framework state between tests
     * 
     * This should be called before each test to ensure clean state.
     * Add new state clearing here as needed when framework components
     * are found to persist state between requests.
     */
    public static function resetFrameworkState(): void
    {
        // Clear user authentication state
        \TN\TN_Core\Model\User\User::clearActiveUser();

        // Clear session data
        $_SESSION = [];

        // Clear any cached HTTP request instances
        self::clearRequestInstances();

        // Clear any other static caches that might exist
        self::clearStaticCaches();

        // Allow projects to clear their own state
        self::clearProjectState();

        // Reset error reporting to default
        error_reporting(E_ALL);

        // Clear any output buffers that might be hanging around
        // But be careful not to interfere with PHPUnit's output buffering
        if (ob_get_level() > 1) {
            while (ob_get_level() > 1) {
                ob_end_clean();
            }
        }
    }

    /**
     * Clear HTTP request static instances
     */
    private static function clearRequestInstances(): void
    {
        // Use reflection to clear static $instance in Request classes
        $requestClasses = [
            \TN\TN_Core\Model\Request\HTTPRequest::class,
            \TN\TN_Core\Model\Request\Command::class,
        ];

        foreach ($requestClasses as $class) {
            if (class_exists($class)) {
                $reflection = new \ReflectionClass($class);
                if ($reflection->hasProperty('instance')) {
                    $property = $reflection->getProperty('instance');
                    $property->setAccessible(true);
                    $property->setValue(null, null);
                }
            }
        }
    }

    /**
     * Clear any static caches in framework components
     * 
     * Add clearing for new static caches here as they're discovered
     */
    private static function clearStaticCaches(): void
    {
        // Clear any package/stack caches if they exist
        // This is where we'd add clearing for other static state

        // Example: if Stack class has static caches
        // \TN\TN_Core\Model\Package\Stack::clearCache();
    }


    /**
     * Hook for project-specific state clearing
     * 
     * Projects can override this method in their BaseTestCase to add
     * project-specific state clearing without modifying framework code.
     * 
     * Example in project BaseTestCase:
     * ```php
     * protected function clearProjectState(): void
     * {
     *     MyProjectClass::clearStaticCache();
     *     MyOtherClass::resetState();
     * }
     * ```
     */
    public static function clearProjectState(): void
    {
        // Override in project BaseTestCase if needed
    }

    /**
     * Reset global PHP state
     * 
     * Resets PHP globals that might affect framework behavior
     */
    public static function resetGlobalState(): void
    {
        // Reset superglobals to empty state
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];
        $_FILES = [];

        // Don't reset $_SERVER as it contains important test environment info
        // Don't reset $_ENV as it contains configuration

        // Reset headers if any were set
        if (function_exists('headers_list')) {
            foreach (headers_list() as $header) {
                header_remove(explode(':', $header)[0]);
            }
        }

        // Reset HTTP response code
        http_response_code(200);
    }

    /**
     * Set test database for API components
     * 
     * During functional tests, API components need to use the same database
     * as the test environment, not the main database.
     * 
     * @param string $testDatabase Test database name
     */
    public static function setTestDatabase(string $testDatabase): void
    {
        // Store original values for restoration
        if (!isset($_ENV['ORIGINAL_MYSQL_DB'])) {
            $_ENV['ORIGINAL_MYSQL_DB'] = $_ENV['MYSQL_DB'] ?? '';
            $_ENV['ORIGINAL_REDIS_PREFIX'] = $_ENV['REDIS_PREFIX'] ?? '';
        }

        // Set test database for TestClient to use immediately before API calls
        $_ENV['TEST_DATABASE'] = $testDatabase;

        // Also set MYSQL_DB for immediate use
        $_ENV['MYSQL_DB'] = $testDatabase;

        // CRITICAL: Override REDIS_PREFIX to include database name for cache isolation
        // This creates completely separate cache namespaces for different databases
        $_ENV['REDIS_PREFIX'] = ($_ENV['ORIGINAL_REDIS_PREFIX'] ?? 'mp') . ':' . $testDatabase . ':';

        // Clear any existing database connections so they reconnect to test DB
        \TN\TN_Core\Model\Storage\DB::closeConnections();

        // Note: Redis client will pick up new prefix on next getInstance() call
    }


    /**
     * Restore original database configuration
     */
    public static function restoreOriginalDatabase(): void
    {
        if (isset($_ENV['ORIGINAL_MYSQL_DB'])) {
            $_ENV['MYSQL_DB'] = $_ENV['ORIGINAL_MYSQL_DB'];
            $_ENV['REDIS_PREFIX'] = $_ENV['ORIGINAL_REDIS_PREFIX'];
            unset($_ENV['ORIGINAL_MYSQL_DB']);
            unset($_ENV['ORIGINAL_REDIS_PREFIX']);

            // Clear connections to force reconnection to original DB
            \TN\TN_Core\Model\Storage\DB::closeConnections();
        }
    }

    /**
     * Full reset - both framework and global state
     * 
     * This is the main method that should be called between tests
     */
    public static function resetAll(): void
    {
        self::resetFrameworkState();
        // Don't reset global state as it interferes with TestClient
        // Global state will be set by TestClient as needed
    }
}
