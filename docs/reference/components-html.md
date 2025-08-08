# HTML Components

## Overview

HTML Components in the TN Framework create interactive user interface elements with server-side rendering, client-side functionality, and automatic reloading capabilities. This guide covers creating page components, templates, TypeScript integration, and routing.

## Creating an HTML Component

### Step 1: Propose Component Name

Always confirm the component name and location first:

**Component Name**: `UserProfile`  
**Fully Qualified Class**: `FBG\FBG_Main\Component\User\UserProfile`  
**Directory**: `src/FBG/FBG_Main/Component/User/UserProfile/`

### Step 2: Create Controller Route

Add the component route to an existing controller or create a new one:

```php
<?php
namespace FBG\FBG_Main\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Path;
use TN\TN_Core\Attribute\Component;

class User extends Controller
{
    #[Path('user/profile')]
    #[Component(\FBG\FBG_Main\Component\User\UserProfile::class)]
    public function profile(): void {}
    
    #[Path('component/reload/user-profile')]  
    #[Component(\FBG\FBG_Main\Component\User\UserProfile::class)]
    public function profileComponent(): void {}
}
```

**Important Routing Rules:**
- Use fully qualified class names with leading backslash in `#[Component]` attributes
- NEVER use `use` statements for component references in attributes
- Create both page route (`user/profile`) and reload route (`component/reload/user-profile`)
- Reload routes follow pattern: `component/reload/{kebab-case-name}`

### Step 3: Create Component Directory Structure

```
src/FBG/FBG_Main/Component/User/UserProfile/
├── UserProfile.php      # Required: Main component class
├── UserProfile.tpl      # Required: Smarty template
├── UserProfile.ts       # Optional: TypeScript functionality  
└── _UserProfile.scss    # Optional: Component styles
```

### Step 4: Create Component Class

```php
<?php
namespace FBG\FBG_Main\Component\User;

use TN\TN_Core\Component\HTMLComponent;
use FBG\FBG_Main\Model\User\User;

class UserProfile extends HTMLComponent
{
    private User $user;
    
    public function prepare(): void
    {
        // Get user from route parameter or current user
        $userId = $this->getRouteParam('userId') ?? User::getActive()->id;
        
        $this->user = User::readFromId($userId);
        if (!$this->user) {
            throw new \Exception('User not found');
        }
        
        // Set reload route for this component
        $this->reloadRoute = 'user.profileComponent';
    }
    
    public function getTemplateVariables(): array
    {
        return [
            'user' => $this->user,
            'isOwnProfile' => $this->user->id === User::getActive()->id,
            'profileStats' => $this->getProfileStats()
        ];
    }
    
    private function getProfileStats(): array
    {
        return [
            'articlesCount' => count($this->user->articles),
            'commentsCount' => count($this->user->comments),
            'joinDate' => date('F Y', $this->user->createdTs)
        ];
    }
}
```

### Step 5: Create Template File

```smarty
{* src/FBG/FBG_Main/Component/User/UserProfile/UserProfile.tpl *}
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    <div class="user-profile">
        <div class="user-profile__header">
            <img src="{$user->avatar}" alt="{$user->name}" class="user-profile__avatar">
            <div class="user-profile__info">
                <h1 class="user-profile__name">{$user->name|escape}</h1>
                <p class="user-profile__username">@{$user->username|escape}</p>
                <p class="user-profile__join-date">Member since {$profileStats.joinDate}</p>
            </div>
            
            {if $isOwnProfile}
                <button class="btn btn--primary user-profile__edit-btn" data-action="edit">
                    Edit Profile
                </button>
            {/if}
        </div>
        
        <div class="user-profile__stats">
            <div class="user-profile__stat">
                <span class="user-profile__stat-value">{$profileStats.articlesCount}</span>
                <span class="user-profile__stat-label">Articles</span>
            </div>
            <div class="user-profile__stat">
                <span class="user-profile__stat-value">{$profileStats.commentsCount}</span>
                <span class="user-profile__stat-label">Comments</span>
            </div>
        </div>
        
        <div class="user-profile__content">
            {if $user->bio}
                <div class="user-profile__bio">
                    <h3>About</h3>
                    <p>{$user->bio|escape|nl2br}</p>
                </div>
            {/if}
            
            <div class="user-profile__recent-activity">
                <h3>Recent Activity</h3>
                {* Activity content would go here *}
            </div>
        </div>
    </div>
</div>
```

**Template Requirements:**
- Wrap in `<div class="{$classAttribute}" id="{$idAttribute}">`
- Include `data-reload-url="{path route=$reloadRoute}"` for reloadable components
- Use `{$variable|escape}` for all user-generated content
- Follow BEM naming convention for CSS classes
- Use semantic HTML structure

### Step 6: Add TypeScript File (Optional)

```typescript
// src/FBG/FBG_Main/Component/User/UserProfile/UserProfile.ts
import { HTMLComponent } from '../../../../TN_Core/Component/HTMLComponent';

export class UserProfile extends HTMLComponent {
    protected observe(): void {
        this.bindEditButton();
        this.bindAvatarUpload();
    }
    
    private bindEditButton(): void {
        const editBtn = this.element.querySelector('[data-action="edit"]') as HTMLButtonElement;
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                this.showEditModal();
            });
        }
    }
    
    private bindAvatarUpload(): void {
        const avatar = this.element.querySelector('.user-profile__avatar') as HTMLImageElement;
        if (avatar) {
            avatar.addEventListener('click', () => {
                if (this.isOwnProfile()) {
                    this.openAvatarUploader();
                }
            });
        }
    }
    
    private isOwnProfile(): boolean {
        return this.element.dataset.isOwnProfile === '1';
    }
    
    private showEditModal(): void {
        // Open edit profile modal
        const modal = document.createElement('div');
        modal.className = 'modal modal--edit-profile';
        modal.innerHTML = this.getEditModalContent();
        document.body.appendChild(modal);
        
        // Handle modal events
        this.bindModalEvents(modal);
    }
    
    private openAvatarUploader(): void {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.addEventListener('change', (e) => {
            this.handleAvatarUpload(e.target as HTMLInputElement);
        });
        input.click();
    }
    
    private handleAvatarUpload(input: HTMLInputElement): void {
        const file = input.files?.[0];
        if (file) {
            const formData = new FormData();
            formData.append('avatar', file);
            
            fetch('/api/user/upload-avatar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload component to show new avatar
                    this.reload();
                }
            });
        }
    }
    
    private getEditModalContent(): string {
        return `
            <div class="modal__backdrop">
                <div class="modal__dialog">
                    <div class="modal__header">
                        <h2>Edit Profile</h2>
                        <button class="modal__close" data-action="close">&times;</button>
                    </div>
                    <div class="modal__body">
                        <!-- Edit form would go here -->
                    </div>
                </div>
            </div>
        `;
    }
    
    private bindModalEvents(modal: HTMLElement): void {
        const closeBtn = modal.querySelector('[data-action="close"]');
        closeBtn?.addEventListener('click', () => {
            modal.remove();
        });
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
}
```

### Step 7: Add SCSS File (Optional)

```scss
// src/FBG/FBG_Main/Component/User/UserProfile/_UserProfile.scss
.user-profile {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    
    &__header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    &__avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        transition: opacity 0.2s;
        
        &:hover {
            opacity: 0.8;
        }
    }
    
    &__info {
        flex: 1;
    }
    
    &__name {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    &__username {
        margin: 0 0 0.25rem 0;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    &__join-date {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.9rem;
    }
    
    &__edit-btn {
        align-self: flex-start;
    }
    
    &__stats {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    &__stat {
        text-align: center;
        
        &-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        &-label {
            display: block;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
    }
    
    &__content {
        display: grid;
        gap: 2rem;
    }
    
    &__bio {
        h3 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
        }
        
        p {
            line-height: 1.6;
            color: var(--text-color);
        }
    }
    
    &__recent-activity {
        h3 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
        }
    }
}

// Responsive design
@media (max-width: 768px) {
    .user-profile {
        padding: 1rem;
        
        &__header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        
        &__stats {
            justify-content: center;
        }
    }
}
```

### Step 8: Update Component Maps

After adding TypeScript or SCSS files, update the component maps:

```bash
docker exec nginx php src/run.php components/map
```

## Advanced Component Patterns

### Components with Form Handling

```php
class EditProfile extends HTMLComponent
{
    private User $user;
    private array $errors = [];
    
    public function prepare(): void
    {
        $this->user = User::getActive();
        
        // Handle form submission
        if ($_POST) {
            $this->handleFormSubmission();
        }
    }
    
    private function handleFormSubmission(): void
    {
        try {
            $this->user->update([
                'first' => $_POST['first'] ?? '',
                'last' => $_POST['last'] ?? '',
                'bio' => $_POST['bio'] ?? ''
            ]);
            
            // Redirect after successful update
            header('Location: /user/profile');
            exit;
            
        } catch (ValidationException $e) {
            $this->errors = $e->getErrors();
        }
    }
    
    public function getTemplateVariables(): array
    {
        return [
            'user' => $this->user,
            'errors' => $this->errors,
            'csrfToken' => $this->generateCSRFToken()
        ];
    }
}
```

### Components with Dynamic Content Loading

```php
class ArticleList extends HTMLComponent
{
    private array $articles = [];
    private int $page = 1;
    private int $perPage = 10;
    
    public function prepare(): void
    {
        $this->page = (int)($this->getQueryParam('page') ?? 1);
        $this->loadArticles();
        $this->reloadRoute = 'articles.listComponent';
    }
    
    private function loadArticles(): void
    {
        $offset = ($this->page - 1) * $this->perPage;
        
        $this->articles = Article::searchByProperties([
            'status' => 'published',
            'limit' => $this->perPage,
            'offset' => $offset,
            'orderBy' => 'publishedTs DESC'
        ]);
    }
    
    public function getTemplateVariables(): array
    {
        return [
            'articles' => $this->articles,
            'page' => $this->page,
            'hasNextPage' => count($this->articles) === $this->perPage,
            'hasPrevPage' => $this->page > 1
        ];
    }
}
```

### Components with Real-time Updates

```typescript
export class LiveChat extends HTMLComponent {
    private lastMessageId: number = 0;
    private pollInterval: number;
    
    protected observe(): void {
        this.startPolling();
        this.bindSendMessage();
    }
    
    private startPolling(): void {
        this.pollInterval = setInterval(() => {
            this.checkForNewMessages();
        }, 2000);
    }
    
    private async checkForNewMessages(): Promise<void> {
        const response = await fetch(`/api/chat/messages?since=${this.lastMessageId}`);
        const data = await response.json();
        
        if (data.messages && data.messages.length > 0) {
            this.appendNewMessages(data.messages);
            this.lastMessageId = Math.max(...data.messages.map(m => m.id));
        }
    }
    
    private appendNewMessages(messages: any[]): void {
        const container = this.element.querySelector('.chat__messages');
        messages.forEach(message => {
            const messageEl = this.createMessageElement(message);
            container.appendChild(messageEl);
        });
        
        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }
    
    protected cleanup(): void {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
}
```

## Component Organization Patterns

### Admin vs User Components

```
src/FBG/FBG_Main/Component/User/
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

### Nested Component Hierarchies

```php
// Controller routing for nested components
class Article extends Controller
{
    #[Path('article/{articleId}/comments')]
    #[Component(\FBG\FBG_CMS\Component\Article\Comments\Comments::class)]
    public function comments(): void {}
    
    #[Path('component/reload/article-comments')]
    #[Component(\FBG\FBG_CMS\Component\Article\Comments\Comments::class)]
    public function commentsComponent(): void {}
}
```

### Reusable Component Traits

```php
trait PaginationTrait
{
    protected int $page = 1;
    protected int $perPage = 20;
    protected int $totalItems = 0;
    
    protected function setupPagination(): void
    {
        $this->page = max(1, (int)($this->getQueryParam('page') ?? 1));
    }
    
    protected function getPaginationData(): array
    {
        $totalPages = ceil($this->totalItems / $this->perPage);
        
        return [
            'currentPage' => $this->page,
            'totalPages' => $totalPages,
            'hasPrevious' => $this->page > 1,
            'hasNext' => $this->page < $totalPages,
            'previousPage' => max(1, $this->page - 1),
            'nextPage' => min($totalPages, $this->page + 1)
        ];
    }
}

class ArticleList extends HTMLComponent
{
    use PaginationTrait;
    
    public function prepare(): void
    {
        $this->setupPagination();
        $this->loadArticles();
    }
}
```

## Template Best Practices

### Include External Templates

```smarty
{* Include framework templates *}
{include file="TN_Core/Component/Loading/Loading.tpl"}

{* Include package-specific templates *}
{include file="FBG_Main/Component/User/UserCard/UserCard.tpl" user=$article->user}

{* Pass variables to included templates *}
{include file="FBG_CMS/Component/Article/ArticleCard/ArticleCard.tpl" 
         article=$article 
         showExcerpt=true 
         showAuthor=false}
```

### Conditional Content

```smarty
{if $user->hasPermission('admin')}
    <div class="admin-controls">
        <button class="btn btn--danger" data-action="delete">Delete</button>
    </div>
{/if}

{if $articles}
    <div class="article-list">
        {foreach $articles as $article}
            <article class="article-card">
                <h3>{$article->title|escape}</h3>
                <p>{$article->excerpt|escape}</p>
            </article>
        {/foreach}
    </div>
{else}
    <div class="empty-state">
        <p>No articles found.</p>
    </div>
{/if}
```

### Loading States

```smarty
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    <div class="component-content" {if $isLoading}style="display: none;"{/if}>
        {* Main component content *}
    </div>
    
    {if $isLoading}
        {include file="TN_Core/Component/Loading/Loading.tpl"}
    {/if}
</div>
```

## Error Handling

### Component-Level Error Handling

```php
class UserProfile extends HTMLComponent
{
    private ?User $user = null;
    private ?string $error = null;
    
    public function prepare(): void
    {
        try {
            $userId = $this->getRouteParam('userId');
            $this->user = User::readFromId($userId);
            
            if (!$this->user) {
                $this->error = 'User not found';
                return;
            }
            
            // Check permissions
            if (!$this->canViewProfile($this->user)) {
                $this->error = 'Access denied';
                return;
            }
            
        } catch (Exception $e) {
            $this->error = 'An error occurred loading the profile';
            error_log('UserProfile error: ' . $e->getMessage());
        }
    }
    
    public function getTemplateVariables(): array
    {
        return [
            'user' => $this->user,
            'error' => $this->error,
            'hasError' => !empty($this->error)
        ];
    }
}
```

### Template Error Display

```smarty
<div class="{$classAttribute}" id="{$idAttribute}">
    {if $hasError}
        <div class="component-error">
            <div class="alert alert--error">
                <i class="icon icon--error"></i>
                <span>{$error}</span>
            </div>
        </div>
    {else}
        {* Normal component content *}
        <div class="user-profile">
            {* Profile content here *}
        </div>
    {/if}
</div>
```

## Best Practices Summary

1. **Component Naming**: Use descriptive, focused names without "View" suffix
2. **Route Attributes**: Always use fully qualified class names with leading backslash
3. **Template Structure**: Wrap in proper div with framework attributes
4. **Data Validation**: Validate all inputs and permissions in `prepare()`
5. **Error Handling**: Gracefully handle errors and show user-friendly messages
6. **TypeScript**: Extend HTMLComponent base class and use `observe()` for initialization
7. **SCSS**: Follow BEM naming convention and use CSS variables
8. **Reload URLs**: Always use exact format `data-reload-url="{path route=$reloadRoute}"`
9. **Template Security**: Escape all user-generated content with `|escape`
10. **Performance**: Use efficient queries and consider caching for expensive operations

## Schema Integration

HTML Components that display model data should:
- Load only necessary data in `prepare()`
- Use efficient queries (`searchByProperties()`)
- Handle missing/deleted records gracefully
- Validate user permissions before displaying sensitive data
- Use caching for expensive computations 