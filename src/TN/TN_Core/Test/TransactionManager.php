<?php

namespace TN\TN_Core\Test;

use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Storage\Cache;

/**
 * Transaction Manager
 * Manages database transactions for test isolation.
 * 
 * Provides fast test isolation by wrapping each test in a database transaction
 * that is rolled back after the test completes, ensuring a clean state without
 * the overhead of truncating/deleting data.
 */
class TransactionManager
{
    private DB $db;
    private bool $inTransaction = false;
    private array $savepoints = [];
    private int $savepointCounter = 0;
    private bool $flushCacheOnRollback = true;
    private static ?TransactionManager $activeInstance = null;

    public function __construct(DB $db)
    {
        $this->db = $db;

        // Disable autocommit for test isolation
        // This ensures that individual SQL statements don't auto-commit
        // and can be properly rolled back with the transaction
        $this->db->exec("SET autocommit = 0");

        // Set this as the active instance for DB class to use
        self::$activeInstance = $this;
    }

    /**
     * Begin a transaction for test isolation
     * 
     * @return void
     */
    public function beginTransaction(): void
    {
        if ($this->inTransaction) {
            // Already in transaction, create a savepoint
            $savepointName = 'test_sp_' . (++$this->savepointCounter);
            $this->db->exec("SAVEPOINT {$savepointName}");
            $this->savepoints[] = $savepointName;
        } else {
            // Ensure autocommit is disabled before starting transaction
            $this->db->exec("SET autocommit = 0");
            // Start new transaction
            $this->db->beginTransaction();
            $this->inTransaction = true;
        }
    }

    /**
     * Rollback the current transaction or savepoint
     * 
     * @return void
     */
    public function rollback(): void
    {
        if (!empty($this->savepoints)) {
            // Rollback to most recent savepoint
            $savepointName = array_pop($this->savepoints);
            $this->db->exec("ROLLBACK TO SAVEPOINT {$savepointName}");
        } elseif ($this->inTransaction) {
            // Rollback main transaction
            $this->db->rollback();
            $this->inTransaction = false;

            // Flush cache after transaction rollback to ensure clean state
            if ($this->flushCacheOnRollback) {
                Cache::deleteAll();
            }
        }
    }

    /**
     * Commit the current transaction or release savepoint
     * 
     * @return void
     */
    public function commit(): void
    {
        if (!empty($this->savepoints)) {
            // Release most recent savepoint
            $savepointName = array_pop($this->savepoints);
            $this->db->exec("RELEASE SAVEPOINT {$savepointName}");
        } elseif ($this->inTransaction) {
            // Commit main transaction
            $this->db->commit();
            $this->inTransaction = false;
        }
    }

    /**
     * Check if currently in a transaction
     * 
     * @return bool True if in transaction
     */
    public function isInTransaction(): bool
    {
        return $this->inTransaction || !empty($this->savepoints);
    }

    /**
     * Clean up any open transactions and restore autocommit
     *
     * @return void
     */
    public function cleanup(): void
    {
        // Rollback any open savepoints
        while (!empty($this->savepoints)) {
            $this->rollback();
        }

        // Rollback main transaction if open
        if ($this->inTransaction) {
            $this->rollback();
        }

        // Restore autocommit to default state
        $this->db->exec("SET autocommit = 1");

        // Clear active instance
        if (self::$activeInstance === $this) {
            self::$activeInstance = null;
        }
    }

    /**
     * Execute a callback within a transaction that will be rolled back
     * 
     * This is useful for test setup that needs to be isolated but not persisted.
     * 
     * @param callable $callback Function to execute within transaction
     * @return mixed Return value of callback
     */
    public function executeInRollbackTransaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback();
            return $result;
        } finally {
            $this->rollback();
        }
    }

    /**
     * Enable or disable cache flushing on rollback
     * 
     * @param bool $flush Whether to flush cache on rollback
     * @return void
     */
    public function setFlushCacheOnRollback(bool $flush): void
    {
        $this->flushCacheOnRollback = $flush;
    }

    /**
     * Get the database connection from the active TransactionManager
     * 
     * This allows the DB class to use the same connection that's in a transaction
     * during tests, ensuring all operations participate in the same transaction.
     * 
     * @return DB|null The active transaction's database connection, or null if no active transaction
     */
    public static function getActiveConnection(): ?DB
    {
        return self::$activeInstance?->db;
    }

    /**
     * Check if there's an active TransactionManager
     * 
     * @return bool True if there's an active TransactionManager with a database connection
     */
    public static function hasActiveConnection(): bool
    {
        return self::$activeInstance !== null;
    }
}
