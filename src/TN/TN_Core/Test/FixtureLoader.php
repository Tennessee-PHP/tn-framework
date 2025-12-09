<?php

namespace TN\TN_Core\Test;

/**
 * Fixture Loader
 * Loads and manages JSON test fixtures with relationship resolution.
 * 
 * This class provides a clean way to load test data from JSON files,
 * with support for:
 * - Nested fixture relationships
 * - Dynamic ID assignment
 * - Fixture caching for performance
 * - Validation and error handling
 */
class FixtureLoader
{
    private string $fixtureDirectory;
    private array $loadedFixtures = [];
    private array $fixtureCache = [];
    private int $nextId = 1000; // Start IDs at 1000 to avoid conflicts

    public function __construct(string $fixtureDirectory)
    {
        $this->fixtureDirectory = rtrim($fixtureDirectory, '/');

        if (!is_dir($this->fixtureDirectory)) {
            throw new \InvalidArgumentException("Fixture directory does not exist: {$this->fixtureDirectory}");
        }
    }

    /**
     * Load a fixture from JSON file
     * 
     * @param string $fixturePath Path to fixture file (relative to fixture directory)
     * @return array Loaded fixture data with resolved relationships
     */
    public function load(string $fixturePath): array
    {
        // Check cache first
        if (isset($this->fixtureCache[$fixturePath])) {
            return $this->fixtureCache[$fixturePath];
        }

        $fullPath = $this->fixtureDirectory . '/' . ltrim($fixturePath, '/');

        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("Fixture file not found: {$fullPath}");
        }

        $jsonContent = file_get_contents($fullPath);
        if ($jsonContent === false) {
            throw new \RuntimeException("Could not read fixture file: {$fullPath}");
        }

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in fixture file {$fullPath}: " . json_last_error_msg());
        }

        // Process the fixture data
        $processedData = $this->processFixtureData($data, $fixturePath);

        // Cache the processed data
        $this->fixtureCache[$fixturePath] = $processedData;

        return $processedData;
    }

    /**
     * Load multiple fixtures
     * 
     * @param array $fixturePaths Array of fixture paths
     * @return array Array of loaded fixtures keyed by path
     */
    public function loadMultiple(array $fixturePaths): array
    {
        $fixtures = [];
        foreach ($fixturePaths as $path) {
            $fixtures[$path] = $this->load($path);
        }
        return $fixtures;
    }

    /**
     * Process fixture data, resolving relationships and assigning IDs
     * 
     * @param array $data Raw fixture data
     * @param string $fixturePath Path for context in error messages
     * @return array Processed fixture data
     */
    private function processFixtureData(array $data, string $fixturePath): array
    {
        // If this is an array of fixtures, process each one
        if ($this->isArrayOfFixtures($data)) {
            $processed = [];
            foreach ($data as $key => $item) {
                $processed[$key] = $this->processFixtureItem($item, $fixturePath);
            }
            return $processed;
        } else {
            // Single fixture
            return $this->processFixtureItem($data, $fixturePath);
        }
    }

    /**
     * Process a single fixture item
     * 
     * @param array $item Fixture item data
     * @param string $fixturePath Path for context
     * @return array Processed fixture item
     */
    private function processFixtureItem(array $item, string $fixturePath): array
    {
        $processed = $item;

        // Assign ID if not present
        if (!isset($processed['id'])) {
            $processed['id'] = $this->nextId++;
        }

        // Process nested relationships
        foreach ($processed as $key => $value) {
            if (is_array($value)) {
                if ($this->isFixtureReference($value)) {
                    // This is a reference to another fixture
                    $processed[$key] = $this->resolveFixtureReference($value);
                } elseif ($this->isNestedFixture($value)) {
                    // This is a nested fixture definition
                    $processed[$key] = $this->processFixtureItem($value, $fixturePath);
                } elseif ($this->isArrayOfFixtures($value)) {
                    // This is an array of nested fixtures
                    $nestedProcessed = [];
                    foreach ($value as $nestedKey => $nestedItem) {
                        if (is_array($nestedItem)) {
                            $nestedProcessed[$nestedKey] = $this->processFixtureItem($nestedItem, $fixturePath);
                        } else {
                            $nestedProcessed[$nestedKey] = $nestedItem;
                        }
                    }
                    $processed[$key] = $nestedProcessed;
                }
            }
        }

        return $processed;
    }

    /**
     * Check if data represents an array of fixtures
     * 
     * @param array $data Data to check
     * @return bool True if this is an array of fixtures
     */
    private function isArrayOfFixtures(array $data): bool
    {
        // If it's a numeric array where each element has typical fixture properties
        if (array_keys($data) === range(0, count($data) - 1)) {
            foreach ($data as $item) {
                if (is_array($item) && (isset($item['name']) || isset($item['id']) || count($item) > 2)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if data represents a fixture reference
     * 
     * @param array $data Data to check
     * @return bool True if this is a fixture reference
     */
    private function isFixtureReference(array $data): bool
    {
        return isset($data['$ref']) && is_string($data['$ref']);
    }

    /**
     * Check if data represents a nested fixture
     * 
     * @param array $data Data to check
     * @return bool True if this is a nested fixture
     */
    private function isNestedFixture(array $data): bool
    {
        // A nested fixture typically has properties like name, id, or multiple fields
        return isset($data['name']) || isset($data['id']) || count($data) > 2;
    }

    /**
     * Resolve a fixture reference
     * 
     * @param array $reference Reference data with $ref key
     * @return array Resolved fixture data
     */
    private function resolveFixtureReference(array $reference): array
    {
        $refPath = $reference['$ref'];

        // Load the referenced fixture
        $referencedFixture = $this->load($refPath);

        // If the reference includes additional properties, merge them
        $resolved = $referencedFixture;
        foreach ($reference as $key => $value) {
            if ($key !== '$ref') {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Get all loaded fixtures
     * 
     * @return array All loaded fixtures
     */
    public function getLoadedFixtures(): array
    {
        return $this->loadedFixtures;
    }

    /**
     * Clear fixture cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->fixtureCache = [];
        $this->loadedFixtures = [];
    }

    /**
     * Set the starting ID for auto-generated IDs
     * 
     * @param int $startId Starting ID value
     * @return void
     */
    public function setStartingId(int $startId): void
    {
        $this->nextId = $startId;
    }

    /**
     * Get the next available ID
     * 
     * @return int Next ID
     */
    public function getNextId(): int
    {
        return $this->nextId++;
    }

    /**
     * List all available fixture files
     * 
     * @return array Array of fixture file paths
     */
    public function listAvailableFixtures(): array
    {
        $fixtures = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->fixtureDirectory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $relativePath = str_replace($this->fixtureDirectory . '/', '', $file->getPathname());
                $fixtures[] = $relativePath;
            }
        }

        return $fixtures;
    }
}
