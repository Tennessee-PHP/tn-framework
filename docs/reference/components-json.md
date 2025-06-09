# JSON Components

## Overview

JSON Components in the TN Framework handle API endpoints, form submissions, and AJAX requests. They return JSON responses and are designed for programmatic consumption rather than direct user interface rendering.

## Creating a JSON Component

### Step 1: Propose Component Name

Use action-based names for JSON components:

**Component Name**: `CreateMessage` (not `MessageResponse` or `MessageHandler`)  
**Fully Qualified Class**: `FBG\FBG_Chat\Component\Message\CreateMessage`  
**Directory**: `src/FBG/FBG_Chat/Component/Message/CreateMessage/`

### Step 2: Create Controller Route

```php
<?php
namespace FBG\FBG_Chat\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Path;
use TN\TN_Core\Attribute\Component;

class Message extends Controller
{
    #[Path('api/message/create')]
    #[Component(\FBG\FBG_Chat\Component\Message\CreateMessage::class)]
    public function create(): void {}
    
    #[Path('api/message/update')]
    #[Component(\FBG\FBG_Chat\Component\Message\UpdateMessage::class)]
    public function update(): void {}
    
    #[Path('api/message/delete')]
    #[Component(\FBG\FBG_Chat\Component\Message\DeleteMessage::class)]
    public function delete(): void {}
}
```

**API Route Conventions:**
- Use `/api/` prefix for JSON endpoints
- Use action-based URLs (`/create`, `/update`, `/delete`)
- Group related endpoints under same controller
- Use HTTP methods appropriately (though TN handles via component logic)

### Step 3: Create Component Directory

```
src/FBG/FBG_Chat/Component/Message/CreateMessage/
└── CreateMessage.php    # Only PHP file needed for JSON components
```

### Step 4: Create JSON Component Class

```php
<?php
namespace FBG\FBG_Chat\Component\Message;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Attribute\FromPost;
use TN\TN_Core\Error\ValidationException;
use FBG\FBG_Chat\Model\Conversation\Conversation;
use FBG\FBG_Chat\Model\Conversation\Message;
use FBG\FBG_Main\Model\User\User;

class CreateMessage extends JSON
{
    #[FromPost] public int $conversationId;
    #[FromPost] public string $content;
    
    public function prepare(): void
    {
        // Validate inputs early
        if (empty(trim($this->content))) {
            throw new ValidationException('Message content is required');
        }
        
        if (strlen($this->content) > 2000) {
            throw new ValidationException('Message too long (max 2000 characters)');
        }
        
        // Validate access in one query
        $conversations = Conversation::searchByProperties([
            'id' => $this->conversationId,
            'userId' => User::getActive()->id
        ]);
        
        if (empty($conversations)) {
            throw new ValidationException('Conversation not found or access denied');
        }
        
        $conversation = $conversations[0];
        
        // Create the message
        $message = Message::getInstance();
        $message->update([
            'conversationId' => $this->conversationId,
            'content' => trim($this->content),
            'userId' => User::getActive()->id,
            'createdTs' => time()
        ]);
        
        // Return success response with message data
        $this->data = [
            'id' => $message->id,
            'content' => $message->content,
            'userId' => $message->userId,
            'createdTs' => $message->createdTs,
            'userName' => User::getActive()->name
        ];
    }
}
```

## FromPost Attribute Usage

### Basic Form Data Handling

```php
class UpdateProfile extends JSON
{
    #[FromPost] public string $firstName;
    #[FromPost] public string $lastName;
    #[FromPost] public string $email;
    #[FromPost] public ?string $bio = null;  // Optional field
    
    public function prepare(): void
    {
        $user = User::getActive();
        
        // Validate email format
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
        
        // Check for email uniqueness
        $existing = User::searchByProperty('email', $this->email);
        foreach ($existing as $existingUser) {
            if ($existingUser->id !== $user->id) {
                throw new ValidationException('Email already in use');
            }
        }
        
        // Update user
        $user->update([
            'first' => $this->firstName,
            'last' => $this->lastName,
            'email' => $this->email,
            'bio' => $this->bio ?? ''
        ]);
        
        $this->data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ];
    }
}
```

### Array and Complex Data Types

```php
class UpdatePermissions extends JSON
{
    #[FromPost] public int $userId;
    #[FromPost] public array $roleIds;      // Expects array from form
    #[FromPost] public array $permissions;  // Expects array from form
    
    public function prepare(): void
    {
        // Validate user exists and current user can modify permissions
        $targetUser = User::readFromId($this->userId);
        if (!$targetUser) {
            throw new ValidationException('User not found');
        }
        
        if (!User::getActive()->hasPermission('manage_users')) {
            throw new ValidationException('Access denied');
        }
        
        // Validate role IDs are valid
        foreach ($this->roleIds as $roleId) {
            if (!is_numeric($roleId)) {
                throw new ValidationException('Invalid role ID');
            }
        }
        
        // Process role assignments
        $this->updateUserRoles($targetUser, $this->roleIds);
        $this->updateUserPermissions($targetUser, $this->permissions);
        
        $this->data = [
            'userId' => $targetUser->id,
            'roles' => $this->roleIds,
            'permissions' => $this->permissions
        ];
    }
}
```

### File Upload Handling

```php
class UploadAvatar extends JSON
{
    #[FromPost] public ?array $avatar = null;  // File upload data
    
    public function prepare(): void
    {
        $user = User::getActive();
        
        // Check if file was uploaded
        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('No file uploaded or upload error');
        }
        
        $file = $_FILES['avatar'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new ValidationException('Invalid file type. Only JPEG, PNG, and GIF are allowed');
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new ValidationException('File too large. Maximum size is 5MB');
        }
        
        // Process upload
        $avatarUrl = $this->processAvatarUpload($file);
        
        // Update user avatar
        $user->update(['avatar' => $avatarUrl]);
        
        $this->data = [
            'avatarUrl' => $avatarUrl,
            'userId' => $user->id
        ];
    }
}
```

## Validation Patterns

### Input Validation

```php
class CreateArticle extends JSON
{
    #[FromPost] public string $title;
    #[FromPost] public string $content;
    #[FromPost] public string $status = 'draft';
    #[FromPost] public array $tags = [];
    
    public function prepare(): void
    {
        $errors = [];
        
        // Validate title
        if (empty(trim($this->title))) {
            $errors[] = 'Title is required';
        } elseif (strlen($this->title) > 200) {
            $errors[] = 'Title too long (max 200 characters)';
        }
        
        // Validate content
        if (empty(trim($this->content))) {
            $errors[] = 'Content is required';
        } elseif (strlen($this->content) < 100) {
            $errors[] = 'Content too short (min 100 characters)';
        }
        
        // Validate status
        $validStatuses = ['draft', 'published', 'archived'];
        if (!in_array($this->status, $validStatuses)) {
            $errors[] = 'Invalid status';
        }
        
        // Validate tags
        if (count($this->tags) > 10) {
            $errors[] = 'Too many tags (max 10)';
        }
        
        foreach ($this->tags as $tag) {
            if (!is_string($tag) || strlen($tag) > 50) {
                $errors[] = 'Invalid tag format';
                break;
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        
        // Create article
        $article = Article::getInstance();
        $article->update([
            'title' => trim($this->title),
            'content' => trim($this->content),
            'status' => $this->status,
            'authorId' => User::getActive()->id,
            'createdTs' => time()
        ]);
        
        // Process tags
        $this->processTags($article, $this->tags);
        
        $this->data = [
            'id' => $article->id,
            'title' => $article->title,
            'status' => $article->status,
            'tags' => $this->tags
        ];
    }
}
```

### Authorization Checks

```php
class DeleteArticle extends JSON
{
    #[FromPost] public int $articleId;
    
    public function prepare(): void
    {
        // Combined existence and access check
        $articles = Article::searchByProperties([
            'id' => $this->articleId,
            'authorId' => User::getActive()->id
        ]);
        
        if (empty($articles)) {
            throw new ValidationException('Article not found or access denied');
        }
        
        $article = $articles[0];
        
        // Additional business logic checks
        if ($article->status === 'published' && !User::getActive()->hasPermission('delete_published')) {
            throw new ValidationException('Cannot delete published articles');
        }
        
        // Perform deletion
        $article->erase();
        
        $this->data = [
            'deleted' => true,
            'articleId' => $this->articleId
        ];
    }
}
```

## Response Patterns

### Success Responses

```php
// Simple success
$this->data = [
    'success' => true,
    'id' => $record->id
];

// Success with data
$this->data = [
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
    ],
    'created' => true
];

// Success with metadata
$this->data = [
    'items' => $results,
    'total' => $totalCount,
    'page' => $currentPage,
    'hasNext' => $hasNextPage
];
```

### Error Responses (Handled by Framework)

```php
// Single error
throw new ValidationException('Title is required');

// Multiple errors
throw new ValidationException([
    'title' => 'Title is required',
    'email' => 'Invalid email format'
]);

// Custom error handling
try {
    // Some operation
} catch (Exception $e) {
    throw new ValidationException('Operation failed: ' . $e->getMessage());
}
```

## Complex JSON Components

### Bulk Operations

```php
class BulkUpdateArticles extends JSON
{
    #[FromPost] public array $articleIds;
    #[FromPost] public string $action;
    #[FromPost] public ?string $newStatus = null;
    
    public function prepare(): void
    {
        $user = User::getActive();
        $validActions = ['publish', 'archive', 'delete', 'change_status'];
        
        if (!in_array($this->action, $validActions)) {
            throw new ValidationException('Invalid action');
        }
        
        if (empty($this->articleIds)) {
            throw new ValidationException('No articles selected');
        }
        
        // Validate user can perform action on all articles
        $articles = Article::searchByProperties([
            'id' => $this->articleIds,
            'authorId' => $user->id
        ]);
        
        if (count($articles) !== count($this->articleIds)) {
            throw new ValidationException('Some articles not found or access denied');
        }
        
        $results = [];
        
        foreach ($articles as $article) {
            try {
                switch ($this->action) {
                    case 'publish':
                        $article->update(['status' => 'published', 'publishedTs' => time()]);
                        break;
                    case 'archive':
                        $article->update(['status' => 'archived']);
                        break;
                    case 'delete':
                        $article->erase();
                        break;
                    case 'change_status':
                        if ($this->newStatus) {
                            $article->update(['status' => $this->newStatus]);
                        }
                        break;
                }
                
                $results[] = [
                    'id' => $article->id,
                    'success' => true
                ];
                
            } catch (Exception $e) {
                $results[] = [
                    'id' => $article->id,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->data = [
            'action' => $this->action,
            'results' => $results,
            'totalProcessed' => count($results),
            'successCount' => count(array_filter($results, fn($r) => $r['success']))
        ];
    }
}
```

### Search and Filtering

```php
class SearchUsers extends JSON
{
    #[FromPost] public string $query = '';
    #[FromPost] public array $filters = [];
    #[FromPost] public int $page = 1;
    #[FromPost] public int $perPage = 20;
    #[FromPost] public string $sortBy = 'createdTs';
    #[FromPost] public string $sortOrder = 'desc';
    
    public function prepare(): void
    {
        // Validate inputs
        $this->perPage = min(100, max(1, $this->perPage));
        $this->page = max(1, $this->page);
        
        $validSortFields = ['createdTs', 'username', 'email', 'last', 'first'];
        if (!in_array($this->sortBy, $validSortFields)) {
            $this->sortBy = 'createdTs';
        }
        
        $this->sortOrder = in_array($this->sortOrder, ['asc', 'desc']) ? $this->sortOrder : 'desc';
        
        // Build search criteria
        $searchCriteria = [];
        
        // Text search
        if (!empty($this->query)) {
            $searchCriteria['search'] = $this->query;
        }
        
        // Apply filters
        foreach ($this->filters as $field => $value) {
            if (in_array($field, ['active', 'locked', 'verified']) && is_bool($value)) {
                $searchCriteria[$field] = $value;
            }
        }
        
        // Pagination
        $offset = ($this->page - 1) * $this->perPage;
        $searchCriteria['limit'] = $this->perPage;
        $searchCriteria['offset'] = $offset;
        $searchCriteria['orderBy'] = $this->sortBy . ' ' . strtoupper($this->sortOrder);
        
        // Execute search
        $users = User::searchByProperties($searchCriteria);
        $totalCount = User::countByProperties(array_diff_key($searchCriteria, ['limit', 'offset', 'orderBy']));
        
        // Format results
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->name,
                'active' => $user->active,
                'createdTs' => $user->createdTs
            ];
        }
        
        $this->data = [
            'users' => $results,
            'pagination' => [
                'page' => $this->page,
                'perPage' => $this->perPage,
                'total' => $totalCount,
                'totalPages' => ceil($totalCount / $this->perPage),
                'hasNext' => ($this->page * $this->perPage) < $totalCount,
                'hasPrev' => $this->page > 1
            ],
            'search' => [
                'query' => $this->query,
                'filters' => $this->filters,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder
            ]
        ];
    }
}
```

## Security Considerations

### CSRF Protection

```php
class SecurityAwareComponent extends JSON
{
    #[FromPost] public string $csrfToken;
    
    public function prepare(): void
    {
        // Validate CSRF token
        if (!$this->validateCSRFToken($this->csrfToken)) {
            throw new ValidationException('Invalid CSRF token');
        }
        
        // Continue with component logic
        // ...
    }
    
    private function validateCSRFToken(string $token): bool
    {
        // Implementation depends on your CSRF protection system
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
```

### Rate Limiting

```php
class RateLimitedComponent extends JSON
{
    public function prepare(): void
    {
        $user = User::getActive();
        
        // Check rate limits
        if (!$this->checkRateLimit($user)) {
            throw new ValidationException('Rate limit exceeded. Please try again later.');
        }
        
        // Continue with component logic
        // ...
        
        // Update rate limit counter
        $this->updateRateLimit($user);
    }
    
    private function checkRateLimit(User $user): bool
    {
        // Implementation depends on your rate limiting system
        $key = 'rate_limit_' . $user->id;
        $attempts = (int)Cache::get($key, 0);
        return $attempts < 10; // Max 10 requests per minute
    }
    
    private function updateRateLimit(User $user): void
    {
        $key = 'rate_limit_' . $user->id;
        $attempts = (int)Cache::get($key, 0);
        Cache::set($key, $attempts + 1, 60); // 1 minute TTL
    }
}
```

### Input Sanitization

```php
class SecureInput extends JSON
{
    #[FromPost] public string $content;
    #[FromPost] public string $title;
    
    public function prepare(): void
    {
        // Sanitize inputs
        $this->content = $this->sanitizeHtml($this->content);
        $this->title = $this->sanitizeText($this->title);
        
        // Continue with component logic
        // ...
    }
    
    private function sanitizeHtml(string $html): string
    {
        // Use HTML Purifier or similar
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
    
    private function sanitizeText(string $text): string
    {
        return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
    }
}
```

## Frontend Integration

### JavaScript/TypeScript Usage

```typescript
// Client-side code for calling JSON components
class MessageAPI {
    static async createMessage(conversationId: number, content: string): Promise<any> {
        const response = await fetch('/api/message/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                conversationId: conversationId.toString(),
                content: content
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Request failed');
        }
        
        return data;
    }
    
    static async uploadAvatar(file: File): Promise<any> {
        const formData = new FormData();
        formData.append('avatar', file);
        
        const response = await fetch('/api/user/upload-avatar', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Upload failed');
        }
        
        return data;
    }
}

// Usage in components
export class ChatComponent extends HTMLComponent {
    private async sendMessage(content: string): Promise<void> {
        try {
            const conversationId = parseInt(this.element.dataset.conversationId!);
            const result = await MessageAPI.createMessage(conversationId, content);
            
            // Update UI with new message
            this.addMessageToUI(result);
            
        } catch (error) {
            this.showError(error.message);
        }
    }
}
```

## Error Handling Patterns

### Graceful Error Responses

```php
class RobustComponent extends JSON
{
    public function prepare(): void
    {
        try {
            // Main component logic
            $this->processRequest();
            
        } catch (ValidationException $e) {
            // Re-throw validation errors (handled by framework)
            throw $e;
            
        } catch (Exception $e) {
            // Log unexpected errors
            error_log('Component error: ' . $e->getMessage());
            
            // Return user-friendly error
            throw new ValidationException('An unexpected error occurred. Please try again.');
        }
    }
}
```

### Partial Success Handling

```php
class BatchProcessor extends JSON
{
    public function prepare(): void
    {
        $results = [];
        $errors = [];
        
        foreach ($this->items as $item) {
            try {
                $result = $this->processItem($item);
                $results[] = $result;
            } catch (Exception $e) {
                $errors[] = [
                    'item' => $item,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->data = [
            'success' => count($results),
            'failed' => count($errors),
            'results' => $results,
            'errors' => $errors,
            'partialSuccess' => !empty($results) && !empty($errors)
        ];
    }
}
```

## Best Practices Summary

1. **Component Naming**: Use action-based names (CreateMessage, UpdateProfile)
2. **Route Patterns**: Use `/api/` prefix and RESTful naming
3. **Input Validation**: Validate early and throw ValidationException for user errors
4. **Access Control**: Combine existence and permission checks in single queries
5. **Response Format**: Return structured data with consistent field names
6. **Error Handling**: Let framework handle ValidationException, log unexpected errors
7. **Security**: Validate CSRF tokens, sanitize inputs, implement rate limiting
8. **Performance**: Use efficient queries and avoid N+1 problems
9. **Frontend Integration**: Provide consistent API contracts for client consumption
10. **Data Consistency**: Use transactions for multi-step operations 