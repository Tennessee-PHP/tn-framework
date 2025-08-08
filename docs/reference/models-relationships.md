# Models - Relationships

## Overview

The TN Framework provides powerful relationship management through attributes. This guide covers parent-child relationships, bidirectional associations, and performance optimization for complex data structures.

## Parent-Child Relationships

### Basic Parent-Child Setup

This is the most common relationship pattern in the framework.

#### Child Class Declaration

```php
<?php
namespace FBG\FBG_Chat\Model\Conversation;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Relationships\ParentClass;
use TN\TN_Core\Attribute\Relationships\ParentId;
use TN\TN_Core\Attribute\Relationships\ParentObject;

#[TableName('messages')]
#[ParentClass('FBG\FBG_Chat\Model\Conversation\Conversation')]
class Message implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[ParentId]
    public int $conversationId;
    
    #[ParentObject]
    public Conversation $conversation;
    
    public string $content;
    public int $userId;
    public int $createdTs;
}
```

#### Parent Class Declaration

```php
<?php
namespace FBG\FBG_Chat\Model\Conversation;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Relationships\ChildrenClass;

#[TableName('conversations')]
class Conversation implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    /** @var Message[] */
    #[ChildrenClass('FBG\FBG_Chat\Model\Conversation\Message')]
    public array $messages = [];
    
    public string $title;
    public int $createdTs;
}
```

### Required Attributes for Relationships

#### On Child Classes

1. **`#[ParentClass]`**: Full namespace of the parent class
2. **`#[ParentId]`**: Property holding the parent's ID
3. **`#[ParentObject]`**: Property for the parent object instance

#### On Parent Classes

1. **`#[ChildrenClass]`**: Full namespace of the child class
2. **Array type hint**: `/** @var ChildClass[] */`

## Bidirectional Relationship Usage

### Accessing Parent from Child

```php
// Load a message and access its conversation
$message = Message::readFromId(123);
$conversation = $message->conversation; // Automatically loaded

echo $conversation->title;
```

### Accessing Children from Parent

```php
// Load a conversation and access its messages
$conversation = Conversation::readFromId(456);
$messages = $conversation->messages; // Automatically loaded

foreach ($messages as $message) {
    echo $message->content;
}
```

### Adding Children to Parent

```php
$conversation = Conversation::readFromId(456);

// Create new message
$message = Message::getInstance();
$message->update([
    'conversationId' => $conversation->id,
    'content' => 'Hello world!',
    'userId' => $currentUser->id,
    'createdTs' => Time::getNow()
]);

// The message is automatically added to the conversation's messages array
// and the conversation property is set on the message
```

## Complex Relationship Examples

### Article with Multiple Child Types

```php
// Article.php
#[TableName('articles')]
class Article implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    /** @var Comment[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Article\Comment')]
    public array $comments = [];
    
    /** @var Tag[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Article\Tag')]
    public array $tags = [];
    
    /** @var Revision[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Article\Revision')]
    public array $revisions = [];
    
    public string $title;
    public string $content;
    public int $authorId;
    public int $publishedTs;
}

// Comment.php
#[TableName('article_comments')]
#[ParentClass('FBG\FBG_CMS\Model\Article\Article')]
class Comment implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[ParentId]
    public int $articleId;
    
    #[ParentObject]
    public Article $article;
    
    public string $content;
    public int $userId;
    public int $createdTs;
}

// Tag.php
#[TableName('article_tags')]
#[ParentClass('FBG\FBG_CMS\Model\Article\Article')]
class Tag implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[ParentId]
    public int $articleId;
    
    #[ParentObject]
    public Article $article;
    
    public string $name;
    public string $color = '#000000';
}
```

### Hierarchical Relationships (Tree Structures)

```php
// Category.php - Self-referencing hierarchy
#[TableName('categories')]
#[ParentClass('FBG\FBG_CMS\Model\Category\Category')]  // Self-reference
class Category implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[ParentId]
    #[Optional]  // Root categories have no parent
    public ?int $parentCategoryId = null;
    
    #[ParentObject]
    #[Optional]
    public ?Category $parentCategory = null;
    
    /** @var Category[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Category\Category')]  // Self-reference
    public array $subCategories = [];
    
    public string $name;
    public string $slug;
    public int $sortOrder = 0;
}
```

### User-Generated Content Pattern

```php
// User.php
#[TableName('users')]
class User implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    /** @var Article[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Article\Article')]
    public array $articles = [];
    
    /** @var Comment[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Comment\Comment')]
    public array $comments = [];
    
    public string $username;
    public string $email;
}

// Article.php (with user relationship)
#[TableName('articles')]
#[ParentClass('FBG\FBG_Main\Model\User\User')]
class Article implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[ParentId]
    public int $userId;  // Author
    
    #[ParentObject]
    public User $user;  // Author object
    
    // Article also has its own children
    /** @var Comment[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Comment\Comment')]
    public array $comments = [];
}
```

## Many-to-Many Relationships

For many-to-many relationships, create a junction model:

```php
// User.php
#[TableName('users')]
class User implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    /** @var UserRole[] */
    #[ChildrenClass('FBG\FBG_Main\Model\User\UserRole')]
    public array $userRoles = [];
    
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->userRoles as $userRole) {
            $roles[] = $userRole->role;
        }
        return $roles;
    }
}

// Role.php
#[TableName('roles')]
class Role implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    /** @var UserRole[] */
    #[ChildrenClass('FBG\FBG_Main\Model\User\UserRole')]
    public array $userRoles = [];
    
    public string $name;
    public string $description;
}

// UserRole.php (Junction table)
#[TableName('user_roles')]
#[ParentClass('FBG\FBG_Main\Model\User\User')]
class UserRole implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[ParentId]
    public int $userId;
    
    #[ParentObject]
    public User $user;
    
    // Also relates to Role
    #[ParentClass('FBG\FBG_Main\Model\Role\Role')]
    public int $roleId;
    
    #[ParentObject]
    public Role $role;
    
    public int $assignedTs;
    public ?int $expiresTs = null;
}
```

## Performance Optimization

### Lazy Loading

Children are loaded automatically when first accessed:

```php
$conversation = Conversation::readFromId(123);
// Messages not loaded yet

$messageCount = count($conversation->messages);
// Now messages are loaded from database
```

### Eager Loading for Performance

When you know you'll need children, load them efficiently:

```php
// Load conversation with all messages in single query
$conversation = Conversation::readFromId(123);
$messages = $conversation->messages; // Triggers load

// Process all messages
foreach ($messages as $message) {
    echo $message->content;
}
```

### Efficient Relationship Queries

```php
// GOOD: Single query to find user's articles
$user = User::readFromId(123);
$articles = $user->articles;

// BETTER: If you only need specific articles
$publishedArticles = Article::searchByProperties([
    'userId' => $user->id,
    'status' => 'published'
]);

// BEST: Custom methods for complex queries
class User implements Persistence
{
    public function getPublishedArticles(): array
    {
        return Article::searchByProperties([
            'userId' => $this->id,
            'status' => 'published',
            'publishedTs' => ['>', 0]
        ]);
    }
}
```

### Avoiding N+1 Queries

```php
// BAD: N+1 query problem
$articles = Article::searchByProperty('status', 'published');
foreach ($articles as $article) {
    echo $article->user->username; // Each iteration triggers a query
}

// GOOD: Load users in batch
$articles = Article::searchByProperty('status', 'published');
$userIds = array_unique(array_column($articles, 'userId'));
$users = User::searchByProperty('id', $userIds);
$userMap = [];
foreach ($users as $user) {
    $userMap[$user->id] = $user;
}

foreach ($articles as $article) {
    $user = $userMap[$article->userId];
    echo $user->username;
}
```

## Relationship Validation

### Validating Parent Exists

```php
class Message implements Persistence
{
    protected function customValidate(): void
    {
        $errors = [];
        
        // Validate conversation exists and user has access
        $conversations = Conversation::searchByProperties([
            'id' => $this->conversationId,
            'userId' => User::getActive()->id
        ]);
        
        if (empty($conversations)) {
            $errors[] = 'Conversation not found or access denied';
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
```

### Validating Relationship Constraints

```php
class UserRole implements Persistence
{
    protected function customValidate(): void
    {
        $errors = [];
        
        // Check if user already has this role
        $existing = self::searchByProperties([
            'userId' => $this->userId,
            'roleId' => $this->roleId
        ]);
        
        foreach ($existing as $userRole) {
            if (!isset($this->id) || $userRole->id !== $this->id) {
                $errors[] = 'User already has this role';
                break;
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
```

## Cascade Operations

### Deleting with Children

```php
class Conversation implements Persistence
{
    public function deleteWithChildren(): void
    {
        // Delete all messages first
        foreach ($this->messages as $message) {
            $message->erase();
        }
        
        // Then delete the conversation
        $this->erase();
    }
}
```

### Soft Delete Pattern

```php
class Article implements Persistence
{
    public bool $deleted = false;
    public ?int $deletedTs = null;
    
    public function softDelete(): void
    {
        $this->update([
            'deleted' => true,
            'deletedTs' => Time::getNow()
        ]);
        
        // Also soft delete comments
        foreach ($this->comments as $comment) {
            $comment->softDelete();
        }
    }
    
    public static function getActive(): array
    {
        return self::searchByProperty('deleted', false);
    }
}
```

## Common Patterns

### Ordered Children

```php
class Playlist implements Persistence
{
    /** @var PlaylistItem[] */
    #[ChildrenClass('FBG\FBG_Music\Model\Playlist\PlaylistItem')]
    public array $items = [];
    
    public function getOrderedItems(): array
    {
        $items = $this->items;
        usort($items, fn($a, $b) => $a->sortOrder <=> $b->sortOrder);
        return $items;
    }
    
    public function addItem(Song $song, int $position = null): PlaylistItem
    {
        $item = PlaylistItem::getInstance();
        $item->update([
            'playlistId' => $this->id,
            'songId' => $song->id,
            'sortOrder' => $position ?? $this->getNextSortOrder()
        ]);
        
        return $item;
    }
    
    private function getNextSortOrder(): int
    {
        $maxOrder = 0;
        foreach ($this->items as $item) {
            $maxOrder = max($maxOrder, $item->sortOrder);
        }
        return $maxOrder + 1;
    }
}
```

### Versioned Content

```php
class Document implements Persistence
{
    /** @var DocumentVersion[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Document\DocumentVersion')]
    public array $versions = [];
    
    public function getCurrentVersion(): ?DocumentVersion
    {
        $latest = null;
        foreach ($this->versions as $version) {
            if (!$latest || $version->createdTs > $latest->createdTs) {
                $latest = $version;
            }
        }
        return $latest;
    }
    
    public function createNewVersion(string $content, int $userId): DocumentVersion
    {
        $version = DocumentVersion::getInstance();
        $version->update([
            'documentId' => $this->id,
            'content' => $content,
            'authorId' => $userId,
            'versionNumber' => $this->getNextVersionNumber(),
            'createdTs' => Time::getNow()
        ]);
        
        return $version;
    }
}
```

### Computed Relationship Properties

```php
class User implements Persistence
{
    /** @var Article[] */
    #[ChildrenClass('FBG\FBG_CMS\Model\Article\Article')]
    public array $articles = [];
    
    #[Impersistent]
    public ?int $articleCount = null;
    
    #[Impersistent]
    public ?int $publishedArticleCount = null;
    
    public function __get(string $prop): mixed
    {
        return match ($prop) {
            'articleCount' => $this->articleCount ??= count($this->articles),
            'publishedArticleCount' => $this->publishedArticleCount ??= count(
                array_filter($this->articles, fn($a) => $a->status === 'published')
            ),
            default => parent::__get($prop)
        };
    }
}
```

## Best Practices for Relationships

1. **Use Full Namespaces**: Always provide complete class paths in relationship attributes
2. **Document Array Types**: Use `/** @var ChildClass[] */` for IDE support
3. **Validate Access**: Check permissions when loading related data
4. **Optimize Queries**: Use `searchByProperties()` instead of loading all children
5. **Handle Orphans**: Consider what happens when parent/child is deleted
6. **Cache Expensive Queries**: Use `#[Impersistent]` for computed relationship data
7. **Use Junction Models**: For many-to-many relationships, create explicit junction tables
8. **Batch Operations**: Load related data in batches to avoid N+1 queries

## Schema Generation Impact

Relationships automatically generate:
- Foreign key constraints between tables
- Proper column types for ID fields
- Indexes on foreign key columns for performance

The framework analyzes relationship attributes and creates appropriate database schema during schema generation. 