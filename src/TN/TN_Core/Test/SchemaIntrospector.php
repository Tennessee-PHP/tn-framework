<?php

namespace TN\TN_Core\Test;

use TN\TN_Core\Model\Storage\DB;

/**
 * Schema Introspector
 * Auto-detects project tables from the schema system for database cleanup.
 * 
 * This class provides methods to discover what tables exist in a project
 * by leveraging the framework's schema system, eliminating the need for
 * hardcoded table lists in test cleanup routines.
 */
class SchemaIntrospector
{
    private DB $db;
    private ?array $cachedTables = null;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    /**
     * Get all table names in the database
     * 
     * @return array Array of table names
     */
    public function getAllTables(): array
    {
        if ($this->cachedTables !== null) {
            return $this->cachedTables;
        }

        $stmt = $this->db->query("SHOW TABLES");
        $tables = [];

        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $this->cachedTables = $tables;
        return $tables;
    }

    /**
     * Get tables that likely contain test data
     * 
     * This method filters out system tables and focuses on application tables
     * that are likely to contain test data that needs cleanup.
     * 
     * @return array Array of table names
     */
    public function getTestDataTables(): array
    {
        $allTables = $this->getAllTables();

        // Filter out system/framework tables that don't need test cleanup
        $systemTables = [
            'cache',
            'sessions',
            'migrations',
            'schema_version',
            'performance_logs',
            'error_logs'
        ];

        return array_filter($allTables, function ($table) use ($systemTables) {
            return !in_array($table, $systemTables);
        });
    }

    /**
     * Get foreign key relationships for proper cleanup ordering
     * 
     * Returns an array of table dependencies to ensure foreign key
     * constraints are respected during cleanup.
     * 
     * @return array Array of table => [dependent_tables] relationships
     */
    public function getForeignKeyDependencies(): array
    {
        $dependencies = [];
        $tables = $this->getTestDataTables();

        foreach ($tables as $table) {
            $stmt = $this->db->query("
                SELECT 
                    REFERENCED_TABLE_NAME
                FROM 
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE 
                    TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '{$table}'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            $referencedTables = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($row['REFERENCED_TABLE_NAME']) {
                    $referencedTables[] = $row['REFERENCED_TABLE_NAME'];
                }
            }

            if (!empty($referencedTables)) {
                $dependencies[$table] = $referencedTables;
            }
        }

        return $dependencies;
    }

    /**
     * Get tables ordered for safe cleanup (respecting foreign keys)
     * 
     * Returns tables in an order where dependent tables come before
     * the tables they reference, allowing safe deletion without
     * foreign key constraint violations.
     * 
     * @return array Array of table names in cleanup order
     */
    public function getTablesInCleanupOrder(): array
    {
        $tables = $this->getTestDataTables();
        $dependencies = $this->getForeignKeyDependencies();

        // Simple topological sort for cleanup order
        $ordered = [];
        $remaining = $tables;
        $maxIterations = count($tables) * 2; // Prevent infinite loops
        $iterations = 0;

        while (!empty($remaining) && $iterations < $maxIterations) {
            $iterations++;
            $addedThisRound = [];

            foreach ($remaining as $table) {
                // Check if all dependencies are already in ordered list
                $canAdd = true;
                if (isset($dependencies[$table])) {
                    foreach ($dependencies[$table] as $dependency) {
                        if (in_array($dependency, $remaining)) {
                            $canAdd = false;
                            break;
                        }
                    }
                }

                if ($canAdd) {
                    $ordered[] = $table;
                    $addedThisRound[] = $table;
                }
            }

            // Remove added tables from remaining
            $remaining = array_diff($remaining, $addedThisRound);

            // If we didn't add any tables this round, add them anyway to prevent deadlock
            if (empty($addedThisRound) && !empty($remaining)) {
                $ordered = array_merge($ordered, $remaining);
                break;
            }
        }

        return $ordered;
    }

    /**
     * Generate cleanup SQL for test data
     * 
     * Generates DELETE statements for cleaning up test data while respecting
     * foreign key constraints. Uses ID ranges to target test data specifically.
     * 
     * @param int $minTestId Minimum ID for test data (default: 998)
     * @param int $maxTestId Maximum ID for test data (default: 999999)
     * @return array Array of SQL DELETE statements
     */
    public function generateCleanupSQL(int $minTestId = 998, int $maxTestId = 999999): array
    {
        $tables = $this->getTablesInCleanupOrder();
        $cleanupQueries = [];

        foreach ($tables as $table) {
            // Check if table has an 'id' column
            $stmt = $this->db->query("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '{$table}' 
                AND COLUMN_NAME = 'id'
            ");

            if ($stmt->fetch()) {
                // Table has an id column, use ID range for cleanup
                $cleanupQueries[] = "DELETE FROM {$table} WHERE id >= {$minTestId} AND id <= {$maxTestId}";
            } else {
                // No id column, check for common test identifier patterns
                $stmt = $this->db->query("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '{$table}' 
                    AND (COLUMN_NAME LIKE '%Id' OR COLUMN_NAME LIKE '%_id')
                ");

                $idColumns = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $idColumns[] = $row['COLUMN_NAME'];
                }

                if (!empty($idColumns)) {
                    // Build cleanup query using foreign key columns
                    $conditions = [];
                    foreach ($idColumns as $column) {
                        $conditions[] = "{$column} >= {$minTestId} AND {$column} <= {$maxTestId}";
                    }
                    $cleanupQueries[] = "DELETE FROM {$table} WHERE " . implode(' OR ', $conditions);
                }
            }
        }

        return $cleanupQueries;
    }

    /**
     * Clear cached table information
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->cachedTables = null;
    }
}
