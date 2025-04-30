<?php

namespace TN\TN_Core\CLI\Schema;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

class All extends CLI
{
    private function getTableNameFromSchema(string $schema): string
    {
        if (preg_match('/CREATE TABLE IF NOT EXISTS `([^`]+)`/', $schema, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function getTableDependencies(string $schema): array
    {
        $dependencies = [];
        // Match all FOREIGN KEY references
        if (preg_match_all('/FOREIGN KEY\s*\([^)]+\)\s*REFERENCES\s*`([^`]+)`/', $schema, $matches)) {
            $dependencies = array_unique($matches[1]);
        }
        return $dependencies;
    }

    private function sortSchemasByDependency(array $tableInfo): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        // Helper function for depth-first topological sort
        $visit = function($tableName) use (&$visit, &$sorted, &$visited, &$visiting, $tableInfo) {
            // Check for circular dependency
            if (isset($visiting[$tableName])) {
                throw new \Exception("Circular dependency detected involving table: $tableName");
            }

            // Skip if already visited
            if (isset($visited[$tableName])) {
                return;
            }

            $visiting[$tableName] = true;

            // Visit all dependencies first
            foreach ($tableInfo[$tableName]['dependencies'] as $dep) {
                if (isset($tableInfo[$dep])) {
                    $visit($dep);
                }
            }

            unset($visiting[$tableName]);
            $visited[$tableName] = true;
            $sorted[] = $tableInfo[$tableName]['schema'];
        };

        // Visit all tables
        foreach (array_keys($tableInfo) as $tableName) {
            if (!isset($visited[$tableName])) {
                $visit($tableName);
            }
        }

        return $sorted;
    }

    public function run(): void
    {
        try {
            $schemas = MySQL::getAllSchemas();
            foreach ($schemas as $schema) {
                $this->out($schema . PHP_EOL . PHP_EOL);
            }
        } catch (\Exception $e) {
            $this->red('Error getting schemas: ' . $e->getMessage());
        }
    }
}
