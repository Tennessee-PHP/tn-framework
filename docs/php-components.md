# PHP Components

## Overview

The TN Framework provides several types of PHP components for building web applications:

- **HTMLComponent**: Server-rendered web pages with optional client-side functionality
- **JSON**: API endpoints that return JSON responses
- **CLI**: Command-line interface components

This guide covers creating, configuring, and organizing these components within the TN Framework.

## Component Types

### HTMLComponent

HTML Components create interactive user interface elements with server-side rendering, client-side functionality, and automatic reloading capabilities.

#### Basic Structure
```php
<?php
namespace Package\Module\Component\Controller;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Attribute\Components\FromPath;

class ComponentName extends HTMLComponent
{
    #[FromPath] public string $userId;        // From URL path segments
    #[FromQuery] public int $page = 1;        // From ?page=1 query string
    #[FromQuery] public ?string $search = null; // From ?search=term query string
    #[FromPost] public ?string $action = null;  // From POST form data
    
    // Template properties (computed in prepare())
    public array $users = [];
    public int $totalCount = 0;
    public string $pageTitle = '';
    
    public function prepare(): void
    {
        // Build search criteria for model-level filtering
        $searchCriteria = ['active' => true];
        if ($this->search) {
            $searchCriteria['search'] = $this->search;
        }
        
        // Fetch data using model-level filtering
        $this->users = User::searchByProperties($searchCriteria);
        
        // Compute all template data (never call methods in templates!)
        $this->totalCount = count($this->users);
        $this->pageTitle = $this->getPageTitle();
    }
    
    protected function getPageTitle(): string
    {
        return 'Component Title';
    }
}
```

### JSON Components

JSON Components handle API requests and return JSON responses. They extend `TN\TN_Core\Component\Renderer\JSON\JSON`.

#### Basic Structure
```php
<?php
namespace Package\Module\API\Controller;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;

class APIName extends JSON
{
    #[FromPost] public string $name;          // Required form field
    #[FromPost] public string $email;         // Required form field
    #[FromPost] public ?string $description = null; // Optional form field
    #[FromQuery] public ?string $redirect = null;   // Optional redirect URL
    
    public function prepare(): void
    {
        // Validate required parameters (automatically populated by framework)
        if (empty($this->name) || empty($this->email)) {
            throw new ValidationException('Name and email are required');
        }
        
        // Validate email format
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Please enter a valid email address');
        }
        
        // Create new record using form data
        $record = Record::getInstance();
        $record->update([
            'name' => $this->name,
            'email' => $this->email,
            'description' => $this->description ?? '',
            'createdAt' => Time::getNow()
        ]);
        $record->save();
        
        // Set response data (framework handles JSON formatting and errors)
        $this->data = [
            'result' => 'success',
            'message' => 'Record created successfully',
            'record' => $record->toApiArray(),
            'timestamp' => date('c', Time::getNow())
        ];
    }
}
```

### CLI Components

CLI Components handle command-line tasks and scheduled operations.

```php
<?php
namespace Package\Module\Component\Integration;

use TN\TN_Core\CLI\CLI;

class CLIComponentName extends CLI
{
    public function run(): void
    {
        $this->out("Starting process...");
        
        try {
            // Command logic here
            $this->processData();
            $this->green("Completed successfully.");
            
        } catch (\Exception $e) {
            $this->red("Error: " . $e->getMessage());
        }
    }
    
    private function processData(): void
    {
        // Implementation here
    }
}
```

## Parameter Attributes

### Framework Parameter Binding

**NEVER access superglobals directly** - use framework attributes:

```php
// Good - Framework attributes
class MyComponent extends HTMLComponent
{
    #[FromPath] public string $userId;        // URL: /users/123 → $userId = "123"
    #[FromQuery] public int $page = 1;        // URL: ?page=2 → $page = 2
    #[FromQuery] public ?string $search = null; // URL: ?search=term → $search = "term"
    #[FromPost] public string $username;      // POST: username=john → $username = "john"
    #[FromPost] public ?string $email = null; // POST: email=... → $email = "..." (optional)
}

// Bad - Direct superglobal access
class MyComponent extends HTMLComponent
{
    public function prepare(): void
    {
        $userId = $_GET['userId'];           // ❌ Never do this
        $page = (int)$_GET['page'] ?? 1;     // ❌ Never do this
        $username = $_POST['username'];      // ❌ Never do this
    }
}
```

### Parameter Types & Defaults
```php
// Path parameters (required)
#[FromPath] public string $userStub;      // Always required from URL
#[FromPath] public int $eventId;          // Type conversion automatic

// Query parameters (optional with defaults)
#[FromQuery] public int $page = 1;        // Default value
#[FromQuery] public ?string $filter = null; // Nullable optional

// Post parameters (form data)
#[FromPost] public string $username;      // Required form field
#[FromPost] public ?string $bio = null;   // Optional form field
```

## Component Location & Naming

### Directory Structure

Components MUST be placed in the appropriate directory structure:

```
src/Package/Module/Component/Controller/ComponentName/
├── ComponentName.php      # Required: Main component class
├── ComponentName.tpl      # Required for HTMLComponent: Template
├── ComponentName.ts       # Optional: TypeScript functionality
└── _ComponentName.scss    # Optional: Component styles
```

Components MUST be placed in the `{PACKAGE}/{MODULE}/Component` directory. NEVER place components inside Controller directories.

### Naming Conventions

- **Components**: Use verb-noun format (`ListUsers`, `HandleLogin`, `CreateEvent`)
- **APIs**: Use action-based names (`CreateMessage`, `UploadFile`, `DeleteUser`)
- **Don't use the word "View"** - e.g. Don't call a component "ViewProjections", just call it "Projections"
- Keep component names focused on their primary responsibility

### Namespacing

The namespace should match the directory structure:
```php
<?php
namespace Package\Module\Component\Controller\ComponentName;
```

## Controllers and Routing

### Creating Controller Routes

Add component routes to controllers:

```php
<?php
namespace Package\Module\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\UsersOnly;
use TN\TN_Core\Attribute\Component;

class ControllerName extends Controller
{
    // Main page route
    #[Path('admin/users')]
    #[UsersOnly]
    #[Component(\Package\Module\Component\ControllerName\ListUsers\ListUsers::class)]
    public function listUsers(): void {}
    
    // Component reload route - same function name, same component
    #[Path('component/reload/list-users')]
    #[UsersOnly]  
    #[Component(\Package\Module\Component\ControllerName\ListUsers\ListUsers::class)]
    public function listUsersComponent(): void {}
}
```

### Reloadable Components

Components that support AJAX reloading need specific attributes and both main and reload routes:

#### Component Attributes
```php
<?php
namespace TN\TN_Comment\Component\ListComments;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;

/**
 * Reusable comments list component for displaying paginated comments
 */
#[Reloadable]  // Enables AJAX reloading functionality
#[Route('TN_Comment:CommentsController:listComments')]  // Links to controller method
class ListComments extends HTMLComponent
{
    // Component implementation
}
```

#### Controller Routes
```php
<?php
namespace TN\TN_Comment\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Component;

class CommentsController extends Controller
{
    /**
     * Main route - used for initial page loads and direct navigation
     */
    #[Path('comments/list')]
    #[Anyone]
    #[Component(\TN\TN_Comment\Component\ListComments\ListComments::class)]
    public function listComments(): void {}

    /**
     * Reload route - used for AJAX reloads (pagination, filtering, etc.)
     * Same component, different route for framework reload handling
     */
    #[Path('component/reload/list-comments')]
    #[Anyone]
    #[Component(\TN\TN_Comment\Component\ListComments\ListComments::class)]
    public function listCommentsComponent(): void {}
}
```

#### Framework Integration

The framework automatically:
- Uses the main route for initial page loads and direct navigation
- Uses the reload route for AJAX updates (pagination, filtering, form submissions)
- Manages URL parameters and browser history
- Handles loading states and error conditions
- Maintains component state across reloads

#### Template Setup

Ensure your component template includes the reload URL attribute:
```smarty
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {* Component content *}
</div>
```

### Route Attributes

**Authentication requirements:**
- `#[AnonymousOnly]` - Login/register pages only
- `#[UsersOnly]` - Requires authentication
- `#[Anyone]` - Both authenticated and anonymous (rare)

**Component references** - ALWAYS use fully qualified class names:
```php
#[Component(\Package\Module\Component\ControllerName\ComponentName::class)]
```

### API Routes

```php
<?php
namespace Package\Module\Controller;

use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\UsersOnly;
use TN\TN_Core\Attribute\Component;

class APIController extends Controller
{
    #[Path('api/users/create')]
    #[UsersOnly]
    #[Component(\Package\Module\API\Users\CreateUser\CreateUser::class)]
    public function createUser(): void {}
}
```

### Command Routes

```php
<?php
namespace Package\Module\Controller;

use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Route\Component;

class IntegrationController extends \TN\TN_Core\Controller\Controller
{
    #[CommandName('data/import')]
    #[Schedule('0 * * * *')] // Every hour
    #[Component(\Package\Module\Component\Integration\DataImport\DataImport::class)]
    public function dataImport(): void {}
}
```

## Data Layer Integration

### Model Structure

Models provide data to components through the TN Framework persistence system:

```php
<?php
namespace Package\Module\Model;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Constraints\Strlen;

#[TableName('users')]
class User implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[Strlen(max: 100)]
    public string $username;
    
    #[Strlen(max: 255)]
    public string $email;
    
    public bool $isActive = true;
}
```

### Querying in Components

```php
public function prepare(): void
{
    // Efficient property-based search
    $users = User::searchByProperties([
        'isActive' => true,
        'role' => 'admin'
    ]);
    
    // Single record by ID
    $user = User::readFromId($userId);
    
    // Combined existence and access check
    $records = Model::searchByProperties([
        'id' => $id,
        'userId' => $currentUser->id
    ]);
    if (empty($records)) {
        throw new ValidationException('Record not found or access denied');
    }
}
```

## API Serialization

### Model API Output

Models that need API serialization should use the `ApiSerializable` trait with property attributes:

```php
<?php
namespace Package\Module\Model;

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
        // Business logic here
        return EventStatus::Live;
    }
    
    #[ApiComputed('matchupCount')]
    public function getMatchupCount(): int
    {
        return count($this->getMatchups());
    }
}
```

## Error Handling

### Framework Exception Handling

The TN Framework **automatically handles all exceptions** in API components - **never use try-catch in prepare()**:

```php
// Controller automatically wraps prepare() in try-catch
try {
    $renderer->prepare();  // ← Your API prepare() method runs here
    return new HTTPResponse($renderer);
} catch (\Error | \Exception $e) {
    // Framework automatically:
    // 1. Logs error via LoggedError::log()
    // 2. Calls JSON::error() with proper message
    // 3. Returns correct HTTP status codes
    // 4. Shows user-friendly vs admin messages
    
    $renderer = JSON::error($e->getDisplayMessage());
    return new HTTPResponse($renderer, $e->httpResponseCode);
}
```

### Exception Types & HTTP Status Codes

```php
// ValidationException → 400 Bad Request (user-facing errors)
throw new ValidationException('Username is required');
// Returns: {"result": "error", "message": "Username is required"}

// LoginException → 401 Unauthorized (authentication errors)  
throw new LoginException('Invalid credentials');
// Returns: {"result": "error", "message": "Invalid credentials"}

// Any other Exception → 500 Internal Server Error (system errors)
throw new \Exception('Database connection failed');
// Returns: {"result": "error", "message": "An error has occurred - it has been logged!"}
// Admin sees: Full stack trace and error details
```

### Error Handling Best Practices

```php
class MyAPI extends JSON
{
    public function prepare(): void
    {
        // ✅ GOOD: Validate early, throw ValidationException for user errors
        if (empty($this->email)) {
            throw new ValidationException('Email address is required');
        }
        
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Please enter a valid email address');
        }
        
        // ✅ GOOD: Let framework catch system errors automatically
        $user = User::searchByProperty('email', $this->email); // May throw DB exception
        
        // ✅ GOOD: Business logic validation with ValidationException
        if (empty($user)) {
            throw new ValidationException('User not found');
        }
        
        // ✅ GOOD: Set success response
        $this->data = [
            'result' => 'success',
            'user' => $user->toApiArray()
        ];
        
        // ❌ BAD: Never use try-catch in prepare()
        // try {
        //     $user = User::searchByProperty('email', $this->email);
        // } catch (\Exception $e) {
        //     $this->data = ['result' => 'error', 'message' => $e->getMessage()];
        // }
    }
}
```

## Component Organization Patterns

### Admin vs User Components

```
src/Package/Module/Component/Controller/
├── Admin/
│   ├── UserManager/
│   │   ├── UserManager.php
│   │   ├── UserManager.tpl
│   │   └── UserManager.ts
│   └── UserPermissions/
└── UserProfile/
    ├── UserProfile.php
    ├── UserProfile.tpl
    └── UserProfile.ts
```

It's permissible for good organization to group components into sub-directories, especially to separate user-facing and staff-facing components.

### Sub-Component Integration

```php
// In parent component's prepare() method
$this->editUsername = new EditUsername(['userId' => $this->userId]);
$this->editUsername->prepare();

// Reload data after sub-component processing
$this->user = User::readFromId($this->userId);
```

## Component Updates

### Update Component Maps

After adding TypeScript or SCSS files, update the component maps:

```bash
docker exec container-name php src/run.php components/map
```

This regenerates the componentMap.ts file that tells the TN Framework which TypeScript classes to instantiate for each component.

## Load More (Infinite Scroll) Components

### Overview

The TN Framework provides built-in infinite scroll functionality through the `#[LoadMore]` attribute. This creates a seamless user experience where new content loads automatically as users scroll.

### Basic Setup

#### 1. Add LoadMore Attribute

```php
<?php
namespace Package\Module\Component\Controller;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\HTMLComponent\LoadMore;
use TN\TN_Core\Attribute\Components\FromQuery;

#[LoadMore]
class ListItems extends HTMLComponent
{
    #[FromQuery] public int $more = 0;     // AJAX load more flag
    #[FromQuery] public int $fromId = 0;   // Cursor for pagination
    
    public array $items = [];
    public bool $hasMore = false;
    
    public function prepare(): void
    {
        $this->loadItems();
    }
    
    private function loadItems(): void
    {
        $itemsPerLoad = 24;
        
        // Build search with cursor-based pagination
        $search = new SearchArguments();
        $search->sorters = [new SearchSorter('createdAt', SearchSorterDirection::DESC)];
        
        // Add cursor condition for load more requests
        if ($this->more === 1 && $this->fromId > 0) {
            $fromItem = Item::readFromId($this->fromId);
            if ($fromItem) {
                $search->conditions[] = new SearchComparison('`createdAt`', '<', 
                    $fromItem->createdAt->format('Y-m-d H:i:s')
                );
            }
        }
        
        // Limit to itemsPerLoad + 1 to check if there are more items
        $search->limit = new SearchLimit(0, $itemsPerLoad + 1);
        
        // Fetch items
        $results = Item::search($search);
        
        // Check if there are more items
        if (count($results) > $itemsPerLoad) {
            $this->hasMore = true;
            array_pop($results);
        }
        
        $this->items = $results;
    }
}
```

#### 2. Template Structure

```smarty
{* Full page render? Show page wrapper *}
{if $more != "1"}
<div class="{$classAttribute}" id="{$idAttribute}" 
     data-reload-url="{path route=$reloadRoute}"
     {if $supportsLoadMore}data-load-more-url="{path route=$loadMoreRoute}" data-supports-load-more="true"{/if}>
    
    {* Page header, filters, etc. *}
    <div class="header">...</div>
    
    {if $items}
        <div class="{tw component='layout-grid'}" data-items-container="true">
    {else}
        <div class="{tw component='empty-state'}">No items found</div>
    {/if}
{/if}

{* Items content - always rendered *}
{if $items}
    {foreach $items as $item}
        {assign var='isLastItem' value=($item@last)}
        <div class="item-card" data-item-id="{$item->id}"{if $isLastItem} data-has-more="{if $hasMore}true{else}false{/if}"{/if}>
            {* Item content *}
        </div>
    {/foreach}
{/if}

{* Close wrapper if not a load more request *}
{if $more != "1"}
    {if $items}
        </div> {* Close data-items-container *}
    {/if}
    
    {* Load More Status *}
    <div class="load-more-status-container">
        {* Loading state *}
        <div class="{tw component='load-more-trigger'}" data-load-more-state="loading"{if !$hasMore} style="display: none;"{/if}>
            <div class="{tw component='load-more-spinner'}">
                <span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
                <span>Loading more items...</span>
            </div>
        </div>
        
        {* No more state *}
        {if $items}
            <div class="{tw component='load-more-trigger'}" data-load-more-state="no-more"{if $hasMore} style="display: none;"{/if}>
                <div class="text-center text-gray-500 dark:text-gray-400">
                    {icon_material name='list' size='lg' color='text-muted'}
                    <p class="mt-2">No more items found</p>
                </div>
            </div>
        {/if}
    </div>
    
</div>
{/if}
```

### Key Requirements

#### Template Data Attributes

- **`data-items-container="true"`** - Marks the container holding items
- **`data-item-id="{$item->id}"`** - Unique ID for each item (for cursor pagination)
- **`data-has-more="true/false"`** - On last item only, indicates if more items exist
- **`data-load-more-state="loading|no-more"`** - Controls visibility of status messages
- **`data-supports-load-more="true"`** - Enables infinite scroll on component

#### Cursor-Based Pagination

Always use cursor-based pagination for reliable results:

```php
// Use backticks around column names in SearchComparison
$search->conditions[] = new SearchComparison('`createdAt`', '<', $timestamp);
```

#### Partial Rendering

The template must support both full page and partial (AJAX) rendering:
- **Full page**: `$more != "1"` - Renders complete component with status container
- **AJAX load more**: `$more == "1"` - Renders only new items

### LoadMore Routes

The framework automatically provides load more routes when using `#[LoadMore]`:

```php
// Controller routes
#[Path('items')]
#[Component(\Package\Module\Component\Controller\ListItems::class)]
public function listItems(): void {}

// Load more route is automatically available at same URL with ?more=1&fromId=123
```

## Best Practices

### Data Processing
- Handle all data processing and logic in PHP code rather than in templates where possible
- Compute all template data in the `prepare()` method
- Never call methods from templates - store method results in properties
- Use searchByProperties for efficient validation queries
- Keep error messages user-friendly and secure

### Component Responsibilities
- Keep components focused on a single responsibility
- Let framework handle common patterns
- Don't duplicate error handling that exists in parent classes
- Use ValidationException for user-facing validation errors
- Keep properties minimal - don't add success/error fields if parent class handles them

### Performance
- Use efficient queries and consider caching for expensive operations
- Avoid multiple queries when one will do
- When checking both existence and ownership, combine into one query
- Use Time::getNow() for all date generation
- Add traits and property attributes to classes for API output handling

