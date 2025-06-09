# Models - Basic Usage

## Overview

TN Framework models use a custom persistence system built on MySQL with attribute-based configuration. This guide covers creating models, defining properties, validation, and schema generation.

## Creating a Basic Model

### Required Traits and Interfaces

Every persistent model must:
1. Implement the `Persistence` interface
2. Use the `MySQL` trait for database operations
3. Use the `PersistentModel` trait for framework integration

```php
<?php
namespace Package\Module\Model;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('my_models')]
class MyModel implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    // Properties go here
}
```

### Table Name Configuration

Every model MUST specify its table name using the `TableName` attribute:

```php
#[TableName('users')]           // Simple table name
#[TableName('cms_articles')]    // Prefixed table name
```

## Property Types and Attributes

### Basic Property Types

Properties are automatically mapped to appropriate database columns:

```php
class MyModel implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    public string $name;        // Maps to VARCHAR
    public int $count;          // Maps to INT
    public float $amount;       // Maps to DECIMAL(11,2)
    public bool $isActive;      // Maps to TINYINT
    public array $data;         // Maps to TEXT (serialized)
}
```

### String Length Constraints

Use the `Strlen` attribute to control VARCHAR field lengths:

```php
use TN\TN_Core\Attribute\Constraints\Strlen;

class User implements Persistence
{
    #[Strlen(min: 0, max: 100)]
    public string $name;        // VARCHAR(100)
    
    #[Strlen(max: 50)]
    public string $code;        // VARCHAR(50)
    
    public string $description; // TEXT (no Strlen = TEXT field)
}
```

### Email and Content Validation

```php
use TN\TN_Core\Attribute\Constraints\EmailAddress;
use TN\TN_Core\Attribute\Constraints\OnlyContains;

class User implements Persistence
{
    #[EmailAddress]
    public string $email;
    
    #[OnlyContains('A-Za-z0-9_-', 'letters, numbers, underscores and dashes')]
    public string $username;
}
```

### Timestamps

Use the `Timestamp` attribute for Unix timestamp fields:

```php
use TN\TN_Core\Attribute\MySQL\Timestamp;

class Article implements Persistence
{
    #[Timestamp]
    public int $createdTs;
    
    #[Timestamp]
    public int $publishedTs = 0;
}
```

### Enum Fields

For fields with a fixed set of values:

```php
use TN\TN_Core\Attribute\MySQL\Enum;

class Article implements Persistence
{
    #[Enum(['draft', 'published', 'archived'])]
    public string $status = 'draft';
}
```

### Impersistent Properties

Properties that should NOT be stored in the database:

```php
use TN\TN_Core\Attribute\Impersistent;

class User implements Persistence
{
    #[Impersistent]
    public bool $loggedIn = false;
    
    #[Impersistent]
    public ?string $temporaryData = null;
}
```

### Optional Properties

Properties that can be left unset during creation:

```php
use TN\TN_Core\Attribute\Optional;

class User implements Persistence
{
    #[Optional]
    public string $middleName = '';
}
```

## Complete Model Example

```php
<?php
namespace FBG\FBG_Main\Model\User;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\MySQL\Timestamp;
use TN\TN_Core\Attribute\Constraints\EmailAddress;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\Constraints\OnlyContains;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\Optional;

#[TableName('users')]
class User implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[Timestamp]
    public int $createdTs;
    
    #[Strlen(min: 1, max: 50)]
    #[OnlyContains('A-Za-z0-9._-', 'letters, numbers, periods, underscores and dashes')]
    public string $username;
    
    #[EmailAddress]
    public string $email;
    
    #[Strlen(max: 50)]
    public string $first = '';
    
    #[Strlen(max: 50)]
    public string $last = '';
    
    #[Optional]
    #[Strlen(max: 6, min: 6)]
    public string $password;
    
    #[Impersistent]
    public bool $loggedIn = false;
    
    public bool $locked = false;
    public bool $inactive = false;
}
```

## Validation

### Built-in Validation

The framework automatically validates:
- Email addresses with `#[EmailAddress]`
- String length with `#[Strlen]`
- Character restrictions with `#[OnlyContains]`
- Required vs optional fields

### Custom Validation

Add custom validation by implementing the `customValidate()` method:

```php
use TN\TN_Core\Error\ValidationException;

class User implements Persistence
{
    protected function customValidate(): void
    {
        $errors = [];
        
        // Check for unique username
        $existing = self::searchByProperty('username', $this->username);
        foreach ($existing as $user) {
            if (!isset($this->id) || $user->id !== $this->id) {
                $errors[] = 'Username already exists';
                break;
            }
        }
        
        // Check for unique email
        $existing = self::searchByProperty('email', $this->email);
        foreach ($existing as $user) {
            if (!isset($this->id) || $user->id !== $this->id) {
                $errors[] = 'Email already exists';
                break;
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
```

## CRUD Operations

### Creating Models

```php
// Create new instance
$user = User::getInstance();
$user->update([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'first' => 'John',
    'last' => 'Doe',
    'createdTs' => Time::getNow()
]);
// Model is automatically saved
```

### Reading Models

```php
// Read by ID
$user = User::readFromId(123);

// Search by single property
$users = User::searchByProperty('active', true);

// Search by multiple properties
$users = User::searchByProperties([
    'active' => true,
    'locked' => false
]);

// Custom search methods
$user = User::getFromLogin('john@example.com'); // Custom method
```

### Updating Models

```php
$user = User::readFromId(123);
$user->update([
    'first' => 'Jonathan',
    'locked' => true
]);
// Changes are automatically saved
```

### Deleting Models

```php
$user = User::readFromId(123);
$user->erase();
```

## Searching and Querying

### Basic Search Methods

```php
// Find all users
$allUsers = User::getAll();

// Search by property
$activeUsers = User::searchByProperty('active', true);

// Search by multiple properties  
$validUsers = User::searchByProperties([
    'active' => true,
    'locked' => false,
    'verified' => true
]);
```

### Performance Best Practices

#### Use searchByProperties for Multiple Conditions

```php
// GOOD: Single query
$records = Model::searchByProperties([
    'userId' => $userId,
    'status' => 'active'
]);

// BAD: Multiple queries
$records = Model::searchByProperty('userId', $userId);
$filtered = [];
foreach ($records as $record) {
    if ($record->status === 'active') {
        $filtered[] = $record;
    }
}
```

#### Efficient Existence and Access Checks

```php
// GOOD: Combined check
$userArticles = Article::searchByProperties([
    'id' => $articleId,
    'authorId' => $user->id
]);
if (empty($userArticles)) {
    throw new ValidationException('Article not found or access denied');
}

// BAD: Separate checks
$article = Article::readFromId($articleId);
if (!$article) {
    throw new ValidationException('Article not found');
}
if ($article->authorId !== $user->id) {
    throw new ValidationException('Access denied');
}
```

## Schema Generation

### Generating Database Schema

To create the SQL schema for your models:

```bash
# If running locally
php src/run.php schema/all

# If using Docker (remember the nginx container name)
docker exec nginx php src/run.php schema/all
```

### Generated Column Types

The framework automatically maps PHP types to MySQL columns:

| PHP Type | MySQL Type | Notes |
|----------|------------|-------|
| `string` | `VARCHAR(255)` or `TEXT` | Use `#[Strlen]` to control |
| `int` | `INT` | |
| `float` | `DECIMAL(11,2)` | |
| `bool` | `TINYINT` | |
| `array` | `TEXT` | Automatically serialized |
| Timestamps | `BIGINT` | With `#[Timestamp]` attribute |

### Schema Features

Generated schemas include:
- Primary keys (auto-incrementing `id` column)
- Proper column types based on PHP properties
- VARCHAR lengths from `#[Strlen]` attributes
- Indexes for performance (when specified)
- Table comments with full PHP class names

### Database Indexing

Add indexes for frequently queried fields:

```php
use TN\TN_Core\Attribute\MySQL\Index;

class Article implements Persistence
{
    #[Index]
    public int $authorId;
    
    #[Index]
    public string $status;
    
    #[Index(['authorId', 'status'])] // Composite index
    public int $publishedTs;
}
```

## Caching

### Model-Level Caching

Enable caching for expensive operations:

```php
use TN\TN_Core\Attribute\Cache;

#[Cache(version: '1.1', lifespan: 3600)]
class User implements Persistence
{
    // Cached for 1 hour
}
```

### Cache Invalidation

```php
use TN\TN_Core\Model\Storage\Cache;

class User implements Persistence
{
    protected function afterSaveUpdate(array $changedProperties): void
    {
        // Invalidate specific cache keys when model changes
        $cacheKey = self::getCacheKey('user_profile', $this->id);
        Cache::delete($cacheKey);
    }
}
```

## Common Patterns

### Constants for State Management

```php
class Article implements Persistence
{
    const int STATE_DRAFT = 1;
    const int STATE_PUBLISHED = 2;
    const int STATE_ARCHIVED = 3;
    
    public static function getAllStates(): array
    {
        return [
            self::STATE_DRAFT => 'Draft',
            self::STATE_PUBLISHED => 'Published', 
            self::STATE_ARCHIVED => 'Archived'
        ];
    }
    
    public int $state = self::STATE_DRAFT;
}
```

### Magic Getters for Computed Properties

```php
class User implements Persistence
{
    public function __get(string $prop): mixed
    {
        return match ($prop) {
            'name' => trim($this->first . ' ' . $this->last),
            'isAdmin' => $this->hasRole('admin'),
            'profileUrl' => '/user/' . $this->username,
            default => property_exists($this, $prop) ? ($this->$prop ?? null) : null
        };
    }
}
```

### Lifecycle Hooks

```php
class User implements Persistence
{
    protected function beforeSave(array $changedProperties): array
    {
        // Modify data before saving
        if (!isset($this->createdTs)) {
            $this->createdTs = Time::getNow();
            $changedProperties[] = 'createdTs';
        }
        return $changedProperties;
    }
    
    protected function afterSaveInsert(): void
    {
        // Actions after creating new record
        Email::sendWelcome($this->email);
    }
    
    protected function afterSaveUpdate(array $changedProperties): void
    {
        // Actions after updating existing record
        if (in_array('email', $changedProperties)) {
            $this->sendEmailChangeNotification();
        }
    }
}
```

## Error Handling

### Use Framework Exceptions

```php
use TN\TN_Core\Error\ValidationException;

// For user-facing validation errors
if (empty($this->title)) {
    throw new ValidationException('Title is required');
}

// For multiple errors
throw new ValidationException([
    'title' => 'Title is required',
    'email' => 'Invalid email format'
]);
```

### Let Framework Handle Database Errors

```php
// DON'T check return values - framework throws exceptions
$user->update(['email' => 'new@email.com']);

// DON'T try to handle database connection issues
$users = User::searchByProperty('active', true);
```

## Best Practices Summary

1. **Always use required traits**: `MySQL`, `PersistentModel`, and `Persistence` interface
2. **Set table names**: Every model needs `#[TableName]`
3. **Use appropriate attributes**: `#[Strlen]`, `#[EmailAddress]`, `#[Timestamp]`, etc.
4. **Mark computed properties**: Use `#[Impersistent]` for non-stored data
5. **Efficient querying**: Use `searchByProperties()` for multiple conditions
6. **Validate properly**: Implement `customValidate()` for business rules
7. **Handle errors correctly**: Use `ValidationException` for user-facing errors
8. **Performance considerations**: Use indexes and caching for frequently accessed data 