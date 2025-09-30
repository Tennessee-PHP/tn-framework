# Smarty Components

## Component Wrapper

Every component template requires these framework attributes:

```smarty
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {* Component content *}
</div>
```

- `{$classAttribute}` - Framework-provided CSS classes
- `{$idAttribute}` - Unique component instance ID  
- `data-reload-url="{path route=$reloadRoute}"` - URL for AJAX reloads

## Controls

Interactive elements require both `data-request-key` and `data-value` attributes for framework integration:

```smarty
{* Input controls *}
<input 
  type="text" 
  name="username"
  data-request-key="username"
  data-value="{$username|escape}"
  value="{$username|escape}"
/>

{* Select controls *}
<select name="category" data-request-key="category" data-value="{$selectedCategory}">
  <option value="">All</option>
  {foreach $categories as $category}
    <option value="{$category.id|escape}" {if $selectedCategory == $category.id}selected{/if}>
      {$category.name|escape}
    </option>
  {/foreach}
</select>

{* el-select controls *}
<el-select name="year" 
           value="{$selectedYear}"
           data-request-key="year"
           data-value="{$selectedYear}">
  {* select content *}
</el-select>
```

### Required Attributes

- **`data-request-key`** - Parameter name for the control (matches `name` attribute)
- **`data-value`** - Current value of the control (must be updated by TypeScript on change)
- **`name`** - Standard form element name
- **`value`** - Current selected/input value

## Sub-Components

Render sub-components using their render() method:

```smarty
{* Sub-component rendering *}
{$pagination->render()}
{$childComponent->render()}

{* Framework component includes *}
{include file="TN_Core/Component/Loading/Loading.tpl"}
```

## Tailwind CSS Integration (Optional)

If your project uses Tailwind CSS, the framework provides semantic class generation:

```smarty
{* Semantic component classes *}
<button class="{tw component='button-primary'}">Save</button>
<div class="{tw component='card'}">Card content</div>

{* Color translation *}
<span class="text-{$user->getRoleColor()|tw_color}">Username</span>

{* Component with overrides *}
<div class="{tw component='container' custom='pt-8'}">Page content</div>
```

See [tailwind-integration.md](tailwind-integration.md) for complete documentation.

## Load More (Infinite Scroll) Templates

### Template Structure

Load More components require specific template structure for proper infinite scroll functionality:

```smarty
{* Full page render check *}
{if $more != "1"}
<div class="{$classAttribute}" id="{$idAttribute}" 
     data-reload-url="{path route=$reloadRoute}"
     {if $supportsLoadMore}data-load-more-url="{path route=$loadMoreRoute}" data-supports-load-more="true"{/if}>
    
    {* Page content (header, filters, etc.) - only on full page *}
    <div class="page-header">...</div>
    
    {* Items container wrapper *}
    {if $items}
        <div class="{tw component='layout-grid'}" data-items-container="true">
    {else}
        <div class="{tw component='empty-state'}">No items found</div>
    {/if}
{/if}

{* Items content - rendered for both full page and AJAX *}
{if $items}
    {foreach $items as $item}
        {assign var='isLastItem' value=($item@last)}
        <div class="item-card" data-item-id="{$item->id}"{if $isLastItem} data-has-more="{if $hasMore}true{else}false{/if}"{/if}>
            {* Item content here *}
            <h3>{$item->title|escape}</h3>
            <p>{$item->description|escape}</p>
        </div>
    {/foreach}
{/if}

{* Close wrappers and add status container - only on full page *}
{if $more != "1"}
    {if $items}
        </div> {* Close data-items-container *}
    {/if}
    
    {* Load More Status Container *}
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
    
</div> {* Close main component *}
{/if}
```

### Required Data Attributes

#### Component Level
- **`data-supports-load-more="true"`** - Enables infinite scroll on the component
- **`data-load-more-url="{path route=$loadMoreRoute}"`** - AJAX endpoint for loading more items

#### Items Container
- **`data-items-container="true"`** - Marks the container where new items will be appended

#### Individual Items
- **`data-item-id="{$item->id}"`** - Unique identifier for cursor-based pagination
- **`data-has-more="true/false"`** - On last item only, indicates if more items exist

#### Status Messages
- **`data-load-more-state="loading"`** - Loading spinner element
- **`data-load-more-state="no-more"`** - No more items message element

### Template Variables

Load More templates have access to these framework-provided variables:

- **`$more`** - String "1" for AJAX requests, "0" for full page renders
- **`$supportsLoadMore`** - Boolean indicating if component supports load more
- **`$loadMoreRoute`** - Route for AJAX load more requests
- **`$hasMore`** - Boolean indicating if more items are available

### Content Customization

Customize loading and completion messages for different item types:

```smarty
{* Screenshots example *}
<span>Loading more screenshots...</span>
<p>No more screenshots found</p>

{* Users example *}
<span>Loading more users...</span>
<p>No more users found</p>

{* Objects example *}
<span>Loading more objects...</span>  
<p>No more objects found</p>
```

### Partial vs Full Rendering

The template must handle two rendering modes:

#### Full Page Render (`$more != "1"`)
- Renders complete component structure
- Includes page header, navigation, filters
- Includes items container and status messages
- Used for initial page loads and component reloads

#### Partial Render (`$more == "1"`) 
- Renders only new items (no wrapper)
- No page header or navigation elements
- Items have `data-has-more` on the last item
- Used for AJAX load more requests

This dual-mode template ensures clean infinite scroll without duplicate page structure.

That's it. Everything else is in smarty-coding-standards.md.