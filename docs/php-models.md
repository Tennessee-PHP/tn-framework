# PHP Models

## 🚨 CRITICAL FRAMEWORK RULES 🚨

### ❌ NEVER VIOLATE THESE RULES ❌

1. **NEVER use string literals for class references - ALWAYS use `::class`**
   ```php
   // ❌ WRONG - String literals are forbidden
   $className = 'MyNamespace\\MyClass';
   Stack::resolveClassName('Package\\Model\\User');
   
   // ✅ CORRECT - Always use ::class
   $className = MyClass::class;
   Stack::resolveClassName(User::class);
   ```

2. **Framework code MUST NEVER reference site-specific code**
   ```php
   // ❌ WRONG - Framework referencing site code
   use NE\NE_Main\Model\Something;
   Stack::resolveClassName('NE_Main\\Model\\Comment');
   
   // ✅ CORRECT - Framework stays generic
   use TN\TN_Core\Model\Something;
   Stack::resolveClassName(Comment::class);
   ```

**Violating these rules breaks the entire framework architecture and is unforgivable.**

## ⚠️ CRITICAL MODEL PATTERNS

### Creating New Model Instances

**ALWAYS use this pattern - no exceptions:**

```php
// ✅ CORRECT: The ONLY way to create models
$user = User::getInstance();
$user->update([
    'username' => 'john',
    'email' => 'john@example.com',
    'isActive' => true
]);
// Automatically persisted - NEVER call save()
```

**NEVER do any of these:**

```php
// ❌ FORBIDDEN: Never use constructor
$user = new User();

// ❌ FORBIDDEN: Never call save()
$user->save();

// ❌ FORBIDDEN: Never set properties directly
$user->username = 'john';

// ❌ FORBIDDEN: Never pass data to getInstance()
$user = User::getInstance(['username' => 'john']);
```

---

## Creating Models

### 🚨 CRITICAL: PersistentModel is a TRAIT, not a CLASS!

**❌ WRONG - This is the #1 most common mistake:**
```php
// DON'T DO THIS - PersistentModel is NOT a class!
class User extends PersistentModel  // ❌ FATAL ERROR
```

**✅ CORRECT - PersistentModel is a trait:**
```php
<?php
namespace Package\Module\Model;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('users')]
class User implements Persistence  // ✅ Implement Persistence interface
{
    use MySQL;           // ✅ Use MySQL trait for database storage
    use PersistentModel; // ✅ Use PersistentModel trait for core functionality
    
    // Properties here
}
```

**Remember:** 
- `PersistentModel` = **TRAIT** (use it with `use`)
- `Persistence` = **INTERFACE** (implement it with `implements`)
- `MySQL` = **TRAIT** (use it with `use`)

## Property Types

Properties automatically map to appropriate database columns:

```php
public string $name;        // VARCHAR
public int $count;          // INT  
public float $amount;       // DECIMAL(11,2)
public bool $isActive;      // TINYINT
public array $data;         // TEXT (serialized)
```

## Property Attributes

### String Length
```php
use TN\TN_Core\Attribute\Constraints\Strlen;

#[Strlen(max: 100)]
public string $username;    // VARCHAR(100)

public string $bio;         // TEXT (no Strlen = TEXT field)
```

### Validation
```php
use TN\TN_Core\Attribute\Constraints\EmailAddress;
use TN\TN_Core\Attribute\Constraints\OnlyContains;

#[EmailAddress]
public string $email;

#[OnlyContains('A-Za-z0-9_-', 'letters, numbers, underscores and dashes')]
public string $username;
```

### Timestamps
```php
use TN\TN_Core\Attribute\Relationships\CreatedTimestamp;
use TN\TN_Core\Attribute\Relationships\UpdatedTimestamp;

#[CreatedTimestamp]
public DateTime $createdAt;      // Auto-set on creation

#[UpdatedTimestamp] 
public DateTime $updatedAt;      // Auto-updated on save
```

**Important:** Always use `DateTime` objects for all date/time fields in TN Framework projects. Never use integer timestamps.

## Parent-Child Relationships

### Child Model
```php
use TN\TN_Core\Attribute\Relationships\ParentClass;
use TN\TN_Core\Attribute\Relationships\ParentId;
use TN\TN_Core\Attribute\Relationships\ParentObject;

#[TableName('messages')]
#[ParentClass('Package\Module\Model\Conversation')]
class Message implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[ParentId]
    public int $conversationId;
    
    #[ParentObject]
    public Conversation $conversation;
    
    public string $content;
}
```

### Parent Model
```php
use TN\TN_Core\Attribute\Relationships\ChildrenClass;

#[TableName('conversations')]
class Conversation implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    /** @var Message[] */
    #[ChildrenClass('Package\Module\Model\Message')]
    public array $messages = [];
    
    public string $title;
}
```

## Using Relationships

```php
// Access parent from child
$message = Message::readFromId(123);
echo $message->conversation->title;

// Access children from parent
$conversation = Conversation::readFromId(456);
foreach ($conversation->messages as $message) {
    echo $message->content;
}
```

## Model Operations

### Updating Existing Models

To change and persist properties, use `$model->update($data)`; avoid calling `save()` with no arguments or manually tracking changed keys (nothing is persisted when `save()` is called with no arguments).

```php
// ✅ CORRECT: Update existing model
$user = User::readFromId($userId);
$user->update([
    'email' => 'newemail@example.com',
    'isActive' => false
]);
// Automatically persisted - NEVER call save()
```

### Querying Models
```php
// Single record by ID
$user = User::readFromId($userId);

// Search by properties
$users = User::searchByProperties([
    'isActive' => true,
    'role' => 'admin'
]);

// Complex queries with SearchArguments
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;

$searchArgs = new SearchArguments(
    conditions: [
        new SearchComparison('`status`', '=', 'active'),
        new SearchComparison('`userId`', '=', $user->id)
    ]
);
$records = Model::search($searchArgs);
```

## 🚨 SearchComparison Critical Rule

**ALWAYS wrap column names in backticks when using SearchComparison:**

```php
// ✅ CORRECT - Column names with backticks
new SearchComparison('`username`', '=', 'john');
new SearchComparison('`screenshotId`', 'IN', [1, 2, 3]);
new SearchComparison('`age`', '>', 18);
new SearchComparison('`createdAt`', '<', '2024-01-01');

// ❌ WRONG - Without backticks (generates broken SQL)
new SearchComparison('username', '=', 'john');          // 'username' = 'john' [WRONG]
new SearchComparison('screenshotId', 'IN', [1, 2, 3]);  // ? IN (1,2,3) [BROKEN]
```

**Why this matters:**
- Without backticks: Column names are treated as string literals
- With backticks: Column names are treated as actual database columns
- This is the #1 most common SearchComparison mistake

## API Serialization

```php
use Package\Module\Model\Trait\ApiSerializable;
use Package\Module\Model\Trait\ApiProperty;
use Package\Module\Model\Trait\ApiComputed;

class Event implements Persistence
{
    use ApiSerializable;
    
    #[ApiProperty] public string $name;
    #[ApiProperty] public DateTime $start;
    public int $internalId; // Not exposed to API
    
    #[ApiComputed('status')]
    public function getStatus(): EventStatus
    {
        return EventStatus::Live;
    }
}
```

## Schema Generation

Generate database schema from your models:

```bash
docker exec container-name php src/run.php schema/all
```

This automatically creates tables, columns, and indexes based on your model definitions.
