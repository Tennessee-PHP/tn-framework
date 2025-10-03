<?php

namespace TN\TN_Core\Test;

use TN\TN_Core\Model\Storage\DB;

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

    public function __construct(DB $db)
    {
        $this->db = $db;
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
     * Clean up any open transactions
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
}
