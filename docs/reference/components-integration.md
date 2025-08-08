# Component Integration

## Overview

This guide covers advanced component patterns, error handling strategies, component communication, and testing approaches in the TN Framework. These patterns help build robust, maintainable component systems.

## Parent-Child Component Relationships

### Nested Component Architecture

```php
// Parent Component: ArticleView
class ArticleView extends HTMLComponent
{
    private Article $article;
    
    public function prepare(): void
    {
        $articleId = $this->getRouteParam('articleId');
        $this->article = Article::readFromId($articleId);
        
        if (!$this->article) {
            throw new Exception('Article not found');
        }
        
        $this->reloadRoute = 'article.viewComponent';
    }
    
    public function getTemplateVariables(): array
    {
        return [
            'article' => $this->article,
            'canEdit' => $this->canUserEdit(),
            'showComments' => true
        ];
    }
}
```

```smarty
{* ArticleView.tpl *}
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    <article class="article-view">
        <header class="article-view__header">
            <h1>{$article->title|escape}</h1>
            <div class="article-view__meta">
                <span>By {$article->user->name|escape}</span>
                <time>{$article->publishedTs|date_format:'F j, Y'}</time>
            </div>
        </header>
        
        <div class="article-view__content">
            {$article->content}
        </div>
        
        {if $canEdit}
            <div class="article-view__actions">
                <a href="/article/{$article->id}/edit" class="btn btn--primary">Edit</a>
            </div>
        {/if}
    </article>
    
    {* Child component: Comments *}
    {if $showComments}
        <div class="article-view__comments" 
             data-component="Comments" 
             data-article-id="{$article->id}">
            {include file="FBG_CMS/Component/Article/Comments/Comments.tpl" 
                     articleId=$article->id 
                     allowPosting=true}
        </div>
    {/if}
</div>
```

### Dynamic Component Loading

```typescript
export class ArticleView extends HTMLComponent {
    protected observe(): void {
        this.loadChildComponents();
        this.bindActions();
    }
    
    private loadChildComponents(): void {
        // Load comments component dynamically
        const commentsContainer = this.element.querySelector('[data-component="Comments"]');
        if (commentsContainer) {
            this.loadComponent('Comments', commentsContainer);
        }
    }
    
    private async loadComponent(componentName: string, container: Element): Promise<void> {
        const articleId = container.dataset.articleId;
        
        try {
            const response = await fetch(`/component/reload/article-comments?articleId=${articleId}`);
            const html = await response.text();
            container.innerHTML = html;
            
            // Initialize the loaded component
            this.initializeChildComponent(container);
            
        } catch (error) {
            console.error('Failed to load component:', error);
            this.showComponentError(container, 'Failed to load comments');
        }
    }
    
    private initializeChildComponent(container: Element): void {
        // Find and initialize any new components in the loaded HTML
        const components = container.querySelectorAll('[data-component]');
        components.forEach(element => {
            // Framework automatically initializes components
            this.framework.initializeComponent(element);
        });
    }
}
```

## Error Handling and Loading States

### Component-Level Error Boundaries

```php
abstract class RobustComponent extends HTMLComponent
{
    protected ?string $error = null;
    protected bool $isLoading = false;
    
    final public function prepare(): void
    {
        try {
            $this->isLoading = true;
            $this->prepareComponent();
            $this->isLoading = false;
            
        } catch (ValidationException $e) {
            $this->error = $e->getMessage();
            $this->isLoading = false;
            
        } catch (Exception $e) {
            // Log system errors but show user-friendly message
            error_log(get_class($this) . ' error: ' . $e->getMessage());
            $this->error = 'An unexpected error occurred. Please try again.';
            $this->isLoading = false;
        }
    }
    
    abstract protected function prepareComponent(): void;
    
    public function getTemplateVariables(): array
    {
        $variables = [
            'error' => $this->error,
            'isLoading' => $this->isLoading,
            'hasError' => !empty($this->error)
        ];
        
        if (!$this->hasError()) {
            $variables = array_merge($variables, $this->getComponentVariables());
        }
        
        return $variables;
    }
    
    protected function getComponentVariables(): array
    {
        return [];
    }
    
    protected function hasError(): bool
    {
        return !empty($this->error);
    }
}
```

### Universal Error Template Pattern

```smarty
{* Base template for all robust components *}
<div class="{$classAttribute}" id="{$idAttribute}" 
     {if $reloadRoute}data-reload-url="{path route=$reloadRoute}"{/if}>
     
    {if $hasError}
        <div class="component-error">
            <div class="alert alert--error">
                <i class="icon icon--warning"></i>
                <div class="alert__content">
                    <h4>Error</h4>
                    <p>{$error}</p>
                    {if $reloadRoute}
                        <button class="btn btn--secondary btn--sm" data-action="retry">
                            Try Again
                        </button>
                    {/if}
                </div>
            </div>
        </div>
        
    {elseif $isLoading}
        <div class="component-loading">
            {include file="TN_Core/Component/Loading/Loading.tpl"}
        </div>
        
    {else}
        <div class="component-content">
            {* Component-specific content goes here *}
            {block name="component_content"}{/block}
        </div>
    {/if}
</div>
```

## Best Practices Summary

1. **Error Boundaries**: Use robust base classes with proper error handling
2. **Communication**: Implement event-based communication for loose coupling
3. **State Management**: Use shared state for complex inter-component data
4. **Composition**: Build complex UIs by composing smaller components
5. **Lazy Loading**: Load components on-demand to improve performance
6. **Testing**: Write comprehensive tests for component behavior
7. **Caching**: Cache expensive component operations appropriately
8. **Debouncing**: Debounce frequent updates to prevent performance issues
9. **Cleanup**: Always clean up resources in component destruction
10. **Documentation**: Document component APIs and communication contracts 