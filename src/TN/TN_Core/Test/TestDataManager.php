<?php

namespace TN\TN_Core\Test;

use TN\TN_Core\Model\Storage\DB;

/**
 * Test Data Manager
 * Coordinates fixtures, factories, and database operations for test data management.
 * 
 * This class provides a unified interface for:
 * - Loading fixtures and creating database records
 * - Managing test data factories
 * - Coordinating cleanup operations
 * - Tracking created records for rollback
 */
class TestDataManager
{
    private DB $db;
    private FixtureLoader $fixtureLoader;
    private array $factories = [];
    private array $createdRecords = [];
    private bool $trackCreatedRecords = true;

    public function __construct(DB $db, FixtureLoader $fixtureLoader)
    {
        $this->db = $db;
        $this->fixtureLoader = $fixtureLoader;
    }

    /**
     * Register a factory for a specific entity type
     * 
     * @param string $entityType Entity type name (e.g., 'user', 'slate', 'event')
     * @param callable $factory Factory function that creates the entity
     * @return void
     */
    public function registerFactory(string $entityType, callable $factory): void
    {
        $this->factories[$entityType] = $factory;
    }

    /**
     * Create an entity using a registered factory
     * 
     * @param string $entityType Entity type
     * @param array $attributes Override attributes
     * @return mixed Created entity
     */
    public function create(string $entityType, array $attributes = [])
    {
        if (!isset($this->factories[$entityType])) {
            throw new \InvalidArgumentException("No factory registered for entity type: {$entityType}");
        }

        $factory = $this->factories[$entityType];
        $entity = $factory($attributes, $this);

        // Track created record for cleanup
        if ($this->trackCreatedRecords && is_object($entity) && isset($entity->id)) {
            $this->createdRecords[$entityType][] = $entity->id;
        }

        return $entity;
    }

    /**
     * Create multiple entities using a factory
     * 
     * @param string $entityType Entity type
     * @param int $count Number of entities to create
     * @param array $attributes Base attributes for all entities
     * @return array Array of created entities
     */
    public function createMultiple(string $entityType, int $count, array $attributes = []): array
    {
        $entities = [];
        for ($i = 0; $i < $count; $i++) {
            $entities[] = $this->create($entityType, $attributes);
        }
        return $entities;
    }

    /**
     * Load fixture and create corresponding database records
     * 
     * @param string $fixturePath Path to fixture file
     * @param string|null $entityType Entity type for factory (if null, inferred from fixture)
     * @return mixed Created entity or entities
     */
    public function loadFixture(string $fixturePath, ?string $entityType = null)
    {
        $fixtureData = $this->fixtureLoader->load($fixturePath);

        // Infer entity type from fixture path if not provided
        if ($entityType === null) {
            $entityType = $this->inferEntityTypeFromPath($fixturePath);
        }

        // Create database records from fixture data
        if ($this->isArrayOfFixtures($fixtureData)) {
            $entities = [];
            foreach ($fixtureData as $itemData) {
                $entities[] = $this->createFromFixtureData($entityType, $itemData);
            }
            return $entities;
        } else {
            return $this->createFromFixtureData($entityType, $fixtureData);
        }
    }

    /**
     * Create database record from fixture data
     * 
     * @param string $entityType Entity type
     * @param array $fixtureData Fixture data
     * @return mixed Created entity
     */
    private function createFromFixtureData(string $entityType, array $fixtureData)
    {
        // Process nested relationships first
        $processedData = $this->processNestedRelationships($fixtureData);

        // Create the main entity
        return $this->create($entityType, $processedData);
    }

    /**
     * Process nested relationships in fixture data
     * 
     * @param array $data Fixture data
     * @return array Processed data with relationship IDs
     */
    private function processNestedRelationships(array $data): array
    {
        $processed = $data;

        foreach ($data as $key => $value) {
            if (is_array($value) && $this->isNestedEntity($value)) {
                // This is a nested entity - create it first
                $entityType = $this->inferEntityTypeFromKey($key);
                $nestedEntity = $this->createFromFixtureData($entityType, $value);

                // Replace the nested data with just the ID
                $processed[$key . 'Id'] = $nestedEntity->id ?? $nestedEntity['id'];
                unset($processed[$key]);
            } elseif (is_array($value) && $this->isArrayOfNestedEntities($value)) {
                // This is an array of nested entities
                $entityType = $this->inferEntityTypeFromKey($key);
                $nestedEntities = [];
                foreach ($value as $nestedData) {
                    $nestedEntities[] = $this->createFromFixtureData($entityType, $nestedData);
                }
                $processed[$key] = $nestedEntities;
            }
        }

        return $processed;
    }

    /**
     * Check if data represents a nested entity
     * 
     * @param array $data Data to check
     * @return bool True if this is a nested entity
     */
    private function isNestedEntity(array $data): bool
    {
        // A nested entity typically has a name or multiple properties
        return isset($data['name']) || count($data) > 2;
    }

    /**
     * Check if data represents an array of nested entities
     * 
     * @param array $data Data to check
     * @return bool True if this is an array of nested entities
     */
    private function isArrayOfNestedEntities(array $data): bool
    {
        if (!is_array($data) || empty($data)) {
            return false;
        }

        // Check if first element looks like an entity
        $firstElement = reset($data);
        return is_array($firstElement) && $this->isNestedEntity($firstElement);
    }

    /**
     * Check if data represents an array of fixtures
     * 
     * @param array $data Data to check
     * @return bool True if this is an array of fixtures
     */
    private function isArrayOfFixtures(array $data): bool
    {
        return array_keys($data) === range(0, count($data) - 1) &&
            !empty($data) &&
            is_array(reset($data));
    }

    /**
     * Infer entity type from fixture path
     * 
     * @param string $fixturePath Fixture file path
     * @return string Inferred entity type
     */
    private function inferEntityTypeFromPath(string $fixturePath): string
    {
        // Extract entity type from path like "users/test-user.json" -> "user"
        $pathParts = explode('/', $fixturePath);
        $directory = $pathParts[0];

        // Convert plural to singular (simple approach)
        $entityType = rtrim($directory, 's');

        return $entityType;
    }

    /**
     * Infer entity type from relationship key
     * 
     * @param string $key Relationship key
     * @return string Inferred entity type
     */
    private function inferEntityTypeFromKey(string $key): string
    {
        // Convert plural keys to singular entity types
        $singularMappings = [
            'participants' => 'participant',
            'matchups' => 'matchup',
            'guesses' => 'guess',
            'predictions' => 'prediction',
            'events' => 'event',
            'slates' => 'slate',
            'users' => 'user',
        ];

        return $singularMappings[$key] ?? rtrim($key, 's');
    }

    /**
     * Get all created records for cleanup
     * 
     * @return array Created records grouped by entity type
     */
    public function getCreatedRecords(): array
    {
        return $this->createdRecords;
    }

    /**
     * Clear tracking of created records
     * 
     * @return void
     */
    public function clearCreatedRecords(): void
    {
        $this->createdRecords = [];
    }

    /**
     * Enable or disable tracking of created records
     * 
     * @param bool $track Whether to track created records
     * @return void
     */
    public function setTrackCreatedRecords(bool $track): void
    {
        $this->trackCreatedRecords = $track;
    }

    /**
     * Delete all created records (for cleanup)
     * 
     * @return void
     */
    public function deleteCreatedRecords(): void
    {
        // Delete in reverse order to respect foreign key constraints
        $entityTypes = array_reverse(array_keys($this->createdRecords));

        foreach ($entityTypes as $entityType) {
            $ids = $this->createdRecords[$entityType];
            if (!empty($ids)) {
                $tableName = $this->getTableNameForEntityType($entityType);
                $idList = implode(',', array_map('intval', $ids));

                try {
                    $this->db->exec("DELETE FROM {$tableName} WHERE id IN ({$idList})");
                } catch (\PDOException $e) {
                    // Log error but continue cleanup
                    error_log("Failed to delete {$entityType} records: " . $e->getMessage());
                }
            }
        }

        $this->clearCreatedRecords();
    }

    /**
     * Get table name for entity type
     * 
     * @param string $entityType Entity type
     * @return string Table name
     */
    private function getTableNameForEntityType(string $entityType): string
    {
        // Simple mapping - can be extended for complex cases
        $tableMappings = [
            'user' => 'users',
            'event' => 'sport_events',
            'slate' => 'sport_slates',
            'participant' => 'sport_participants',
            'matchup' => 'sport_matchups',
            'entry' => 'entries',
            'guess' => 'entry_guesses',
            'prediction' => 'entry_predictions',
        ];

        return $tableMappings[$entityType] ?? $entityType . 's';
    }

    /**
     * Get the fixture loader instance
     * 
     * @return FixtureLoader Fixture loader
     */
    public function getFixtureLoader(): FixtureLoader
    {
        return $this->fixtureLoader;
    }

    /**
     * Get the database instance
     * 
     * @return DB Database instance
     */
    public function getDatabase(): DB
    {
        return $this->db;
    }
}
