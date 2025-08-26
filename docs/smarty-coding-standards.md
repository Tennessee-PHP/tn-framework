# Smarty Coding Standards

## Overview

Smarty templates in the TN Framework provide server-side rendering for HTML components. These standards ensure secure, maintainable, and consistent template code across all projects.

## Basic Syntax

### Variables

- Use consistent variable notation
- Always escape output (see Security section for details)

```smarty
{* Basic variable display *}
{$userName|escape}
{$userEmail|escape}
{$player.name|escape}

{* Avoid - Unescaped output *}
{$userName} {* Security risk - always escape user data *}
```

### Foreach Loops

- Use `as` syntax for better readability
- Use iteration variables for enhanced functionality
- Use `@key` for accessing array keys

```smarty
{* Basic foreach *}
{foreach $players as $player}
    {$player.name|escape}
{/foreach}

{* With key using @key *}
{foreach $players as $player}
    Player ID: {$player@key|escape} - {$player.name|escape}
{/foreach}

{* With iteration variables *}
{foreach $items as $item}
    {if $item@first}First Item{/if}
    {if $item@last}Last Item{/if}
    Key: {$item@key}
    Index: {$item@index}
    Iteration: {$item@iteration}
    Total: {$item@total}
{/foreach}
```

### Conditionals

- Use clear, readable conditions
- Group complex conditions with parentheses
- Use proper comparison operators

```smarty
{* Simple conditionals *}
{if $user.isAdmin}
    {* Admin content *}
{elseif $user.isEditor}
    {* Editor content *}
{else}
    {* Default view *}
{/if}

{* Complex conditions *}
{if $user.isAdmin && ($user.permissions.edit || $user.permissions.delete)}
    {* Admin controls *}
{/if}

{* Null checks *}
{if $user.profile}
    {$user.profile.bio|escape}
{else}
    No profile information available
{/if}
```

## Template Structure

### Include Files

**Use descriptive names for included files:**

```smarty
{* ✅ GOOD - Descriptive component name *}
{include file="TN_Core/Component/Loading/Loading.tpl"}
{include file="Module/Component/PlayerCard/PlayerCard.tpl"}

{* ❌ BAD - Generic or unclear names *}
{include file="Module/Component/Card/Card.tpl"}
{include file="Module/Component/Thing/Thing.tpl"}
```

**Pass only required variables:**

```smarty
{* ✅ GOOD - Pass the whole object and only what's needed *}
{include file="Module/Component/PlayerCard/PlayerCard.tpl"
    player=$player
    showStats=true
}

{* ❌ BAD - Breaking down objects into individual properties *}
{include file="Module/Component/PlayerCard/PlayerCard.tpl"
    playerId=$player.id
    playerName=$player.name
    playerTeam=$player.team
    playerPosition=$player.position
    showStats=true
}

{* ❌ BAD - Passing unnecessary extra objects *}
{include file="Module/Component/PlayerCard/PlayerCard.tpl"
    player=$player
    game=$game
    season=$season
    league=$league
    showStats=true
}
```

**Always specify full module path starting from module name:**

```smarty
{* ✅ GOOD - Full module path *}
{include file="TN_Core/Component/Loading/Loading.tpl"}
{include file="OP_Main/Component/Auth/LoginForm/LoginForm.tpl"}

{* ❌ BAD - Relative paths or incomplete paths *}
{include file="../Loading/Loading.tpl"}
{include file="Component/Loading/Loading.tpl"}
{include file="Loading.tpl"}
```

### Comments

- Use Smarty comments, not HTML comments
- Document complex logic and required variables
- Include template documentation headers

```smarty
{*
 * Player Profile Template
 * @param int $playerId Player's unique identifier
 * @param array $stats Player's statistics
 * @param bool $isAdmin Whether current user is admin
 * @param string $displayMode Display mode: 'full', 'summary', 'minimal'
 *}

{* Process player statistics for display *}
{if $stats}
    {* Calculate average performance *}
    {assign var="avgScore" value=($stats.totalScore / $stats.gamesPlayed)}
{/if}

{* HTML comments are visible in source - never use these *}
<!-- This comment appears in HTML source -->
```

### Inline CSS

**Never use inline CSS in templates:**

```smarty
{* ❌ BAD - Never use style attributes *}
<div style="display: none;">Hidden content</div>
<div style="background-color: blue;">Colored content</div>
<div style="margin-top: 20px;">Content</div>

{* ✅ GOOD - Use CSS classes *}
<div class="d-none">Hidden content</div>
<div class="bg-primary">Colored content</div>
<div class="mt-3">Content</div>
```

## Security

### Output Escaping

- Always escape variables based on context
- Use appropriate escape modifiers
- Never trust user input

```smarty
{* HTML context - default escaping *}
{$htmlContent|escape}

{* JavaScript context *}
<script>
    const userName = '{$userName|escape:'javascript'}';
    const userData = {$userDataJson|escape:'javascript'};
</script>

{* URL context *}
<a href="profile?id={$userId|escape:'url'}">Profile</a>
<a href="{$dynamicUrl|escape:'url'}">Link</a>

{* CSS context *}
<style>
    .user-theme { color: {$userColor|escape:'css'}; }
</style>

{* Attribute context *}
<div class="{$dynamicClass|escape}" id="{$elementId|escape}">
```

### Form Handling

- Escape form values properly
- Include CSRF protection
- Validate and escape all form data

```smarty
<form method="post" action="{$formAction|escape:'url'}">
    <input type="hidden" name="csrf_token" value="{$csrfToken|escape}">
    <input type="text" name="username" value="{$username|escape}">
    <input type="email" name="email" value="{$email|escape}">
    <textarea name="bio">{$bio|escape}</textarea>
    
    {* Checkbox handling *}
    <input type="checkbox" name="newsletter" value="1" 
           {if $newsletterSubscribed}checked{/if}>
    
    {* Select options *}
    <select name="country">
        {foreach $countries as $code => $name}
            <option value="{$code|escape}" 
                    {if $selectedCountry == $code}selected{/if}>
                {$name|escape}
            </option>
        {/foreach}
    </select>
</form>
```

## TN Framework Integration

### Links and Routing

To generate href attributes for links, use the path modifier, referencing the TN module, then controller name, then finally method name:

```smarty
{* Basic route links *}
<a href="{path route="Module:Controller:method"}">Link Text</a>
<a href="{path route="TN_Core:User:userProfile"}">User Profile</a>

{* Links with GET parameters *}
<a href="{path route="Module:Rankings:display"}?week=1&season=2024">Rankings</a>
<a href="{path route="Module:Content:view"}?id={$contentId}&format=full">View Content</a>

{* CORRECT format - GET variables after path closure *}
<a href="{path route="Module:Rankings:display"}?durationTypeKey=weekly">Weekly Rankings</a>
<a href="{path route="Module:Rankings:display"}?staticTypeKey=dfs&staticSiteKey=draftkings">DFS Rankings</a>

{* INCORRECT format - do not put GET variables as path parameters *}
{* <a href="{path route="Module:Rankings:display" durationTypeKey="weekly"}">Link</a> *}
```

#### Route Link Resolution Process

1. **Component Discovery**
   - Find the target HTMLComponent by matching link text with component's #[Page] attribute title
   - Look for semantic matches (e.g. "Average Draft Position (ADP)" matches "Fantasy Football Average Draft Position (ADP)")
   - Component location follows pattern: {PACKAGE}/{MODULE}/Component/{CONTROLLER_NAME}/ComponentName

2. **Route Extraction**
   - Once component is found, extract route from #[Route] attribute's only parameter
   - Route format in #[Route] is always "MODULE:CONTROLLER:METHOD"
   - Use this exact route string in the {path route=""} template syntax
   - Never construct routes manually - always get them from component Route attributes
   - If you can't find a matching component, leave the href empty

Example:
```smarty
{* Link text: "Average Draft Position (ADP)" *}
{* Component found: Module_Name/Component/ADP/ADP/ADP.php with: *}
{* #[Page('Fantasy Football Average Draft Position (ADP)', ...)] *}
{* #[Route('Module_Name:ADP:aDP')] *}
{* Therefore correct route is: *}
<a href="{path route="Module_Name:ADP:aDP"}">Average Draft Position (ADP)</a>
```

3. **GET Variables**
   - GET variables should be placed AFTER the {path} tag closure using standard URL query string format
   - The query string should start with ? for the first parameter
   - Use & to separate multiple parameters
   - Values should not be quoted in the URL string

## Data Handling

### Iteration Best Practices

```smarty
{* Check for data before iteration *}
{if $articles}
    <div class="article-list">
        {foreach $articles as $article}
            <article class="article-card">
                <h3>{$article.title|escape}</h3>
                <p>{$article.excerpt|escape}</p>
                <time>{$article.publishedDate|escape}</time>
            </article>
        {/foreach}
    </div>
{else}
    <div class="empty-state">
        <p>No articles found.</p>
    </div>
{/if}

{* Handle array keys properly *}
{foreach $userPreferences as $key => $value}
    <div class="preference-item">
        <label>{$key|escape}:</label>
        <span>{$value|escape}</span>
    </div>
{/foreach}
```

### Conditional Display

```smarty
{* User permissions *}
{if $user.hasPermission('admin')}
    <div class="admin-controls">
        <button class="btn btn-danger" data-action="delete">Delete</button>
    </div>
{/if}

{* Content availability *}
{if $content.isPublished && $content.isVisible}
    {include file="Module/Component/Content/ContentDisplay.tpl" content=$content}
{elseif $content.isDraft && $user.canEdit}
    {include file="Module/Component/Content/DraftPreview.tpl" content=$content}
{else}
    <div class="content-unavailable">
        <p>This content is not available.</p>
    </div>
{/if}
```

## Code Style

### Formatting

- Use consistent indentation (4 spaces)
- Align template tags for readability
- Use meaningful variable names

```smarty
{* Good formatting *}
{if $user.isLoggedIn}
    <div class="user-dashboard">
        <h1>Welcome, {$user.name|escape}</h1>
        
        {if $user.notifications}
            <div class="notifications">
                {foreach $user.notifications as $notification}
                    <div class="notification {$notification.type}">
                        {$notification.message|escape}
                    </div>
                {/foreach}
            </div>
        {/if}
    </div>
{/if}
```

