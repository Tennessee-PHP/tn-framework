<?php

namespace TN\TN_Core\Test;

use PHPUnit\Framework\TestCase;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Test\StateManager;

/**
 * Component Test Case
 * Base class for functional tests that interact with framework components via simulated HTTP requests.
 * 
 * This class provides a comprehensive testing foundation with:
 * - Functional HTTP testing through TestClient
 * - Database transaction management for fast test isolation
 * - Schema-driven database cleanup
 * - Fixture loading and management
 * - Both JSON API and HTML response assertions
 */
abstract class ComponentTestCase extends TestCase
{
    protected TestClient $client;
    protected DB $db;
    protected string $testDatabase;
    protected ?TransactionManager $transactionManager = null;
    protected ?SchemaIntrospector $schemaIntrospector = null;
    protected bool $useTransactions = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Determine test database name from environment
        $this->testDatabase = $this->getTestDatabaseName();

        // Get database connection
        $this->db = $this->getDatabaseInstance($this->testDatabase, true);

        // Initialize schema introspector
        $this->schemaIntrospector = new SchemaIntrospector($this->db);

        // Reset all framework state between tests
        StateManager::resetAll();

        // Set test database for API components
        StateManager::setTestDatabase($this->testDatabase);

        // Initialize transaction manager if using transactions
        if ($this->useTransactions) {
            $this->transactionManager = new TransactionManager($this->db);
            $this->transactionManager->beginTransaction();
        } else {
            // Reset database state using schema-driven cleanup
            $this->resetDatabase();
        }

        // Seed test data
        $this->seedTestData();

        // Initialize the test client
        $this->client = $this->createClient();
    }

    protected function tearDown(): void
    {
        // Skip all database cleanup - tests handle their own cleanup
        parent::tearDown();
    }

    /**
     * Get test database name from environment variables
     * 
     * @return string Test database name
     */
    protected function getTestDatabaseName(): string
    {
        if (isset($_ENV['MYSQL_TEST_DB'])) {
            return $_ENV['MYSQL_TEST_DB'];
        } elseif (str_ends_with($_ENV['MYSQL_DB'], '_test')) {
            return $_ENV['MYSQL_DB'];
        } else {
            return $_ENV['MYSQL_DB'] . '_test';
        }
    }

    /**
     * Get database instance for testing
     * 
     * This method can be overridden by subclasses to use project-specific
     * DB classes that extend the framework DB class.
     * 
     * @param string $database Database name
     * @param bool $write Whether write access is needed
     * @return DB Database instance
     */
    protected function getDatabaseInstance(string $database, bool $write = false): DB
    {
        return DB::getInstance($database, $write);
    }

    /**
     * Reset database to clean state for testing
     * 
     * Uses schema introspection to automatically generate cleanup queries.
     * Can be overridden by subclasses for custom cleanup logic.
     */
    protected function resetDatabase(): void
    {
        if ($this->schemaIntrospector) {
            $cleanupQueries = $this->schemaIntrospector->generateCleanupSQL();

            foreach ($cleanupQueries as $query) {
                try {
                    $this->db->exec($query);
                } catch (\PDOException $e) {
                    // Ignore errors for tables that might not exist or have no data
                    // This allows the reset to work even if some tables are empty
                }
            }
        }
    }

    /**
     * Seed test data for the test
     * 
     * This method should be overridden by subclasses to create
     * the necessary test data for each test.
     */
    abstract protected function seedTestData(): void;

    /**
     * Create a new TestClient instance
     */
    protected function createClient(): TestClient
    {
        return new TestClient();
    }

    /**
     * Create an authenticated client with a test token
     * 
     * @param string $token Authentication token
     */
    protected function authenticatedClient(string $token = 'test-token-123'): TestClient
    {
        return $this->createClient()->withToken($token);
    }

    /**
     * Create a client with specific cookies
     * 
     * @param array $cookies Cookie name => value pairs
     */
    protected function clientWithCookies(array $cookies): TestClient
    {
        return $this->createClient()->withCookies($cookies);
    }

    /**
     * Create a client with specific headers
     * 
     * @param array $headers Header name => value pairs
     */
    protected function clientWithHeaders(array $headers): TestClient
    {
        return $this->createClient()->withHeaders($headers);
    }

    /**
     * Assert that a response has the expected API structure
     */
    protected function assertValidAPIResponse(TestResponse $response): void
    {
        $json = $response->getJson();
        $this->assertNotNull($json, 'Response should be valid JSON');
        $this->assertArrayHasKey('result', $json);
        $this->assertArrayHasKey('message', $json);
        // Note: timestamp is only present in success responses, not error responses
    }

    /**
     * Assert that a response indicates success
     */
    protected function assertAPISuccess(TestResponse $response, int $expectedStatusCode = 200): void
    {
        $response->assertStatus($expectedStatusCode);
        $this->assertValidAPIResponse($response);

        $json = $response->getJson();
        $this->assertEquals('success', $json['result'], 'API response should indicate success');
    }

    /**
     * Assert that a response indicates an error
     */
    protected function assertAPIError(TestResponse $response, int $expectedStatusCode = 400): void
    {
        $response->assertStatus($expectedStatusCode);
        $this->assertValidAPIResponse($response);

        $json = $response->getJson();
        $this->assertEquals('error', $json['result'], 'API response should indicate error');
    }

    /**
     * Load fixture data from JSON file
     * 
     * This method should be implemented by subclasses to provide
     * fixture loading functionality.
     * 
     * @param string $fixturePath Path to fixture file
     * @return mixed Loaded fixture data
     */
    protected function loadFixture(string $fixturePath)
    {
        // Default implementation - subclasses should override
        throw new \BadMethodCallException('loadFixture() must be implemented by subclass');
    }

    /**
     * Get the fixture directory path
     * 
     * This method should be implemented by subclasses to specify
     * where fixture files are located.
     * 
     * @return string Path to fixture directory
     */
    abstract protected function getFixtureDirectory(): string;

    /**
     * Disable transaction-based testing for this test class
     * 
     * Call this in setUp() if your tests require persistent data
     * or have issues with transactions.
     */
    protected function disableTransactions(): void
    {
        $this->useTransactions = false;
    }

    /**
     * Execute code within a nested transaction that will be rolled back
     * 
     * Useful for test setup that needs to be isolated but not persisted.
     * 
     * @param callable $callback Function to execute
     * @return mixed Return value of callback
     */
    protected function executeInRollbackTransaction(callable $callback)
    {
        if ($this->transactionManager) {
            return $this->transactionManager->executeInRollbackTransaction($callback);
        } else {
            // If not using transactions, just execute the callback
            return $callback();
        }
    }
}
