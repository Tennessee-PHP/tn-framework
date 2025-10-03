# Setting Up Tests

## Project Structure

```
tests/
├── bootstrap.php
├── fixtures/
│   ├── users/
│   ├── events/
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
        $this->dataManager->registerFactory('user', [$this, 'createUser']);
        $this->dataManager->registerFactory('event', [$this, 'createEvent']);
        // Add more factories as needed
    }

    // Factory methods
    public function createUser(array $attributes = []): object
    {
        static $sequence = 0;
        $sequence++;

        $defaults = [
            'id' => 1000 + $sequence,
            'username' => "testuser{$sequence}",
            'email' => "test{$sequence}@example.com",
            'token' => 'test-token-123'
        ];

        $data = array_merge($defaults, $attributes);
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO users ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return (object) $data;
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
    protected function seedTestData(): void
    {
        $user = $this->create('user', ['username' => 'testuser']);
        $this->testUserId = $user->id;
    }

    public function testApiEndpoint(): void
    {
        $response = $this->authenticatedClient('test-token-123')
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

## 4. Test Patterns

**API Success:**
```php
$response = $this->authenticatedClient()->post('/api/endpoint', $data);
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
$response = $this->authenticatedClient('token')->get('/api/endpoint');
```

## 5. Fixtures (Optional)

Create JSON fixtures in `tests/fixtures/`:

```json
// tests/fixtures/users/admin.json
{
    "id": 1,
    "username": "admin",
    "email": "admin@example.com"
}
```

Load in tests:
```php
$userData = $this->loadFixture('users/admin.json');
$user = $this->create('user', $userData);
```

## Available Assertions

- `$response->assertSuccessful()` - 2xx status
- `$response->assertStatus(404)` - Specific status  
- `$response->getJson()` - Parse JSON response
- `$response->getContent()` - Raw response content

That's it! The framework handles database cleanup, authentication simulation, and HTTP request/response simulation automatically.
