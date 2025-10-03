# Setting Up Tests

## Project Structure

```
tests/
├── bootstrap.php
├── fixtures/
│   ├── create-entry.json
│   ├── get-score.json
│   └── ...
└── YourProject/
    ├── BaseTestCase.php
    └── API/
        └── SomeFeature/
            └── SomeFeatureTest.php
```

## 1. Create BaseTestCase

Create `tests/YourProject/BaseTestCase.php`:

```php
<?php

namespace YourProject\Test;

use TN\TN_Core\Test\ComponentTestCase;

abstract class BaseTestCase extends ComponentTestCase
{
    protected function getFixtureDirectory(): string
    {
        return __DIR__ . '/../fixtures';
    }

    protected function registerFactories(): void
    {
        $this->dataManager->registerFactory('user', [$this, 'createUserModel']);
        $this->dataManager->registerFactory('event', [$this, 'createEventModel']);
        // Add more factories as needed
    }

    // Factory methods create actual model instances
    protected function createUserModel(array $attributes = []): \TN\TN_Core\Model\User\User
    {
        static $sequence = 0;
        $sequence++;

        $user = \TN\TN_Core\Model\User\User::getInstance();
        
        $defaults = [
            'username' => "testuser{$sequence}",
            'email' => "test{$sequence}@example.com",
            'token' => 'test-token-' . $sequence
        ];

        $data = array_merge($defaults, $attributes);
        $user->update($data);
        
        return $user;
    }
}
```

## 2. Update composer.json

Add autoload-dev section:

```json
{
    "autoload-dev": {
        "psr-4": {
            "YourProject\\Test\\": "tests/YourProject/"
        }
    }
}
```

Run: `composer dump-autoload`

## 3. Create Test Files

Create `tests/YourProject/API/SomeFeature/SomeFeatureTest.php`:

```php
<?php

namespace YourProject\Test\API\SomeFeature;

use YourProject\Test\BaseTestCase;

class SomeFeatureTest extends BaseTestCase
{
    protected int $testUserId;
    protected \TN\TN_Core\Model\User\User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize test infrastructure (fixtures + transactions)
        $this->initializeTestInfrastructure();
        
        // Load fixture data and create models
        $fixtureData = $this->fixtureLoader->load('some-feature.json');
        $this->testUser = $this->create('user', $fixtureData['user']);
        $this->testUserId = $this->testUser->id;
    }

    protected function tearDown(): void
    {
        // Transaction rollback handles cleanup automatically
        parent::tearDown();
    }

    public function testApiEndpoint(): void
    {
        $response = $this->authenticatedClient($this->testUser->token)
            ->get('/api/some-endpoint');

        $response->assertSuccessful();
        
        $json = $response->getJson();
        $this->assertEquals('success', $json['result']);
    }

    public function testUnauthenticated(): void
    {
        $response = $this->client->get('/api/protected-endpoint');
        $response->assertStatus(401);
    }
}
```

## 4. Create JSON Fixtures

Create JSON fixtures in `tests/fixtures/`:

```json
// tests/fixtures/some-feature.json
{
    "user": {
        "username": "testuser_feature",
        "email": "test_feature@example.com",
        "token": "test-token-feature-123"
    },
    "event": {
        "name": "Test Event",
        "start": "2025-12-01 18:00:00",
        "sport": "american_football",
        "competition": "NFL"
    }
}
```

## 5. Test Patterns

**API Success:**
```php
$response = $this->authenticatedClient($this->testUser->token)
    ->post('/api/endpoint', $data);
$response->assertSuccessful();
$json = $response->getJson();
$this->assertEquals('success', $json['result']);
```

**API Error:**
```php
$response = $this->client->post('/api/endpoint', $badData);
$response->assertStatus(400);
$json = $response->getJson();
$this->assertEquals('error', $json['result']);
```

**Authentication:**
```php
$response = $this->authenticatedClient($this->testUser->token)->get('/api/endpoint');
```

## 6. Available Assertions

- `$response->assertSuccessful()` - 2xx status
- `$response->assertStatus(404)` - Specific status  
- `$response->getJson()` - Parse JSON response
- `$response->getContent()` - Raw response content

## Key Features

- **Perfect Transaction Isolation**: All database changes are automatically rolled back after each test
- **15x Performance Improvement**: Fixture + transaction system is dramatically faster than manual cleanup
- **JSON Fixtures**: Declarative test data in JSON format
- **Factory System**: Create model instances from fixture data with automatic type conversion
- **Automatic Cleanup**: No manual database cleanup required - transactions handle everything

That's it! The framework handles database transactions, cleanup, authentication simulation, and HTTP request/response simulation automatically.
