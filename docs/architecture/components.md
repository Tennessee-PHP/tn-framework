# Component System

## Overview

The TN Framework uses a powerful component-based architecture that combines PHP, Smarty templates, TypeScript, and SCSS to create reusable, self-contained UI elements.

## Component Structure

A component's directory structure follows this pattern:
```
{PACKAGE}/{MODULE}/Component/{CONTROLLER}/{ComponentName}/
├── ComponentName.php     # Required: Main component class
├── ComponentName.tpl     # Required for HTMLComponent: Smarty template
├── ComponentName.ts      # Optional: TypeScript functionality
└── _ComponentName.scss   # Optional: Component styles
```

## Component Types

### HTML Components

The most common type of component, extending `TN\TN_Core\Component\HTMLComponent`. These components:
- Render HTML using Smarty templates
- Can include TypeScript for client-side functionality
- Can include SCSS for styling
- Support automatic reloading

### Example Component

```php
#[Page('User Profile', 'View user profile details')]
#[Reloadable]
#[Route('User:Profile:view')]
class UserProfile extends HTMLComponent {
    #[FromPath] public string $userId;
    #[FromQuery] public string $tab = 'info';
    public User $user;

    public function prepare(): void {
        $this->user = User::readFromId($this->userId);
        $this->title = "Profile: {$this->user->name}";
    }
}
```

## Component Attributes

### Route Attribute
```php
#[Route('Module:Controller:action')]
```
Links the component to its controller route.

### Page Attribute
```php
#[Page('Title', 'Description')]
```
Defines page metadata for components that represent full pages.

### Reloadable Attribute
```php
#[Reloadable]
```
Enables automatic component reloading.

### Property Attributes
- `#[FromPath]`: Inject URL path parameters
- `#[FromQuery]`: Inject query string parameters

## Templates

### Basic Template Structure
```smarty
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {* Component content *}
</div>
```

### Template Inclusion
Always include templates starting from their module:
```smarty
{include file="TN_Core/Component/Loading/Loading.tpl"}
```

### Template Best Practices
1. Always include the wrapper div with class and id attributes
2. Include data-reload-url for reloadable components
3. Properly escape variables using Smarty's built-in functions
4. Use consistent indentation
5. Follow component hierarchy in template structure

## Child Components

### Creating Child Components
1. Define typed public properties on the parent component
2. Create child components using their constructor
3. Pass initial properties through constructor array
4. Call prepare() on the child component

Example:
```php
class ParentComponent extends HTMLComponent {
    public UserProfile $profile;
    
    public function prepare(): void {
        $this->profile = new UserProfile([
            'userId' => $this->currentUserId
        ]);
        $this->profile->prepare();
    }
}
```

## Component Lifecycle

1. **Construction**: Component is instantiated with initial properties
2. **Preparation**: `prepare()` method is called to set up component state
3. **Child Preparation**: Any child components are prepared
4. **Rendering**: Template is rendered with component state
5. **Client-side Initialization**: TypeScript code is initialized if present
6. **Reloading** (if applicable): Component can be reloaded via AJAX

## TypeScript Integration

### Basic Structure
```typescript
import { HTMLComponent } from '@tn/core';

export class ComponentName extends HTMLComponent {
    protected observe(): void {
        // Initialize component
    }
    
    protected reload(): void {
        // Handle component reload
    }
}
```

### Best Practices
1. Extend base HTMLComponent class
2. Use protected observe() for initialization
3. Implement reload() for reloadable components
4. Use TypeScript types and interfaces
5. Follow component naming conventions

## Styling Guidelines

### SCSS File Structure
```scss
.ComponentName {
    // Component styles
    
    &__element {
        // Element styles
    }
    
    &--modifier {
        // Modifier styles
    }
}
```

### Best Practices
1. Use BEM naming convention
2. Scope styles to component class
3. Use variables for theming
4. Keep styles modular and self-contained
5. Follow responsive design principles

## Component Development Workflow

1. Create controller route
2. Create component directory structure
3. Implement PHP component class
4. Create Smarty template
5. Add TypeScript (if needed)
6. Add SCSS styles (if needed)
7. Run component map update:
   ```bash
   php src/run.php components/map
   ```

## Testing Components

1. **Unit Tests**
   - Test component logic in isolation
   - Mock dependencies
   - Test property initialization

2. **Integration Tests**
   - Test component with child components
   - Test reloading functionality
   - Test template rendering

3. **Browser Tests**
   - Test client-side functionality
   - Test component interactions
   - Test responsive behavior 