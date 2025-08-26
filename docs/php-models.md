# PHP Models

## Creating Models

Every persistent model must implement the `Persistence` interface and use required traits:

```php
<?php
namespace Package\Module\Model;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('users')]
class User implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    // Properties here
}
```

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
public int $createdTs;      // Auto-set on creation

#[UpdatedTimestamp] 
public int $updatedTs;      // Auto-updated on save
```

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

### Creating Models
```php
$user = User::getInstance();
$user->update([
    'username' => 'john',
    'email' => 'john@example.com',
    'isActive' => true
]);
// Automatically saved - never call save()
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
use TN\TN_Core\Model\PersistentModel\Search\SearchCondition;

$searchArgs = new SearchArguments(
    conditions: [
        new SearchCondition('status', 'active'),
        new SearchCondition('userId', $user->id)
    ]
);
$records = Model::search($searchArgs);
```

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
