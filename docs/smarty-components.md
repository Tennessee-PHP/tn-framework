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

Interactive elements use `data-request-key` for framework integration:

```smarty
{* Input controls *}
<input 
  type="text" 
  name="username"
  data-request-key="username"
  value="{$username|escape}"
/>

{* Select controls *}
<select name="category" data-request-key="category">
  <option value="">All</option>
  {foreach $categories as $category}
    <option value="{$category.id|escape}" {if $selectedCategory == $category.id}selected{/if}>
      {$category.name|escape}
    </option>
  {/foreach}
</select>
```

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

That's it. Everything else is in smarty-coding-standards.md.