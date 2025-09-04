# Tailwind CSS Integration (Optional)

The TN Framework provides optional Tailwind CSS integration through a semantic class generation system. This allows projects to define design tokens in JSON and use semantic class names in templates.

## Overview

If your project uses Tailwind CSS, the framework provides:
- **Semantic Design Tokens**: Define colors, components, and sizes in JSON
- **Template Abstraction**: Use meaningful names like "primary" instead of "red-600"
- **Single Source of Truth**: All design decisions centralized in one configuration file
- **Runtime Translation**: PHP converts semantic names to actual Tailwind classes

## Setup

### 1. Create Configuration File

Create `src/css/tailwind.json` in your project:

```json
{
  "colors": {
    "primary": "red-600",
    "primary-hover": "red-700",
    "primary-foreground": "white",
    "secondary": "gray-600",
    "surface": "gray-100",
    "foreground": "gray-900",
    "muted": "gray-500",
    "border": "gray-200"
  },
  "components": {
    "button-primary": "inline-flex items-center px-4 py-2 bg-{primary} text-{primary-foreground} font-medium rounded-lg hover:bg-{primary-hover} transition-colors",
    "card": "bg-{surface} rounded-lg border border-{border} overflow-hidden",
    "container": "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
  },
  "sizes": {
    "button": {
      "small": "px-3 py-1.5 text-sm",
      "large": "px-6 py-3 text-base"
    }
  }
}
```

### 2. Register Smarty Functions

The framework automatically registers these Smarty functions when `TailwindClassGenerator` is available:

```php
// In TemplateEngine.php
$this->registerPlugin('function', 'tw', \TN\TN_Core\Component\TailwindClassGenerator::class . '::generateClasses');
$this->registerPlugin('modifier', 'tw_color', \TN\TN_Core\Component\TailwindClassGenerator::class . '::getColor');
$this->registerPlugin('modifier', 'tw_component', \TN\TN_Core\Component\TailwindClassGenerator::class . '::getComponent');
```

## Usage in Templates

### Basic Component Usage

```smarty
{* Use semantic component names *}
<button class="{tw component='button-primary'}">Save</button>
<div class="{tw component='card'}">Card content</div>
<div class="{tw component='container'}">Page content</div>
```

### Component with Overrides

```smarty
{* Add custom classes or overrides *}
<button class="{tw component='button-primary' custom='gap-2 text-lg'}">
  <span class="material-symbols-outlined">save</span>
  Save Changes
</button>

{* Size variants *}
<button class="{tw component='button-primary' size='large'}">Large Button</button>
```

### Conditional Classes

```smarty
{* Conditional styling *}
<a href="?filter=newest" class="{tw component='button-outline' if=($activeFilter == 'newest') then='bg-primary text-primary-foreground'}">
  Newest
</a>
```

### Color Translation

```smarty
{* Translate semantic color names *}
<span class="text-{$user->getRoleColor()|tw_color}">
  {$user->username}
</span>

{* Direct color usage *}
<div class="bg-{'primary'|tw_color}">Primary background</div>
```

### Available Parameters

The `{tw}` function supports these parameters:

- `component` - Component name from configuration
- `color` - Color name for individual color usage  
- `size` - Size variant (combines with component type)
- `padding`, `margin`, `text`, `bg`, `border` - Override specific properties
- `custom` - Add arbitrary classes
- `if`, `then`, `else` - Conditional classes

## Color Placeholder System

Components can use `{colorName}` placeholders that get replaced with actual Tailwind classes:

```json
{
  "colors": {
    "primary": "red-600",
    "surface": "gray-100"
  },
  "components": {
    "my-component": "bg-{primary} text-{foreground} hover:bg-{surface}"
  }
}
```

This becomes: `bg-red-600 text-gray-900 hover:bg-gray-100`

### Critical Rule: No Hardcoded Colors in Components

**❌ WRONG - Never use literal color names in components:**
```json
{
  "components": {
    "bad-example": "bg-gray-800 text-white border-red-500"
  }
}
```

**✅ CORRECT - Always use semantic color placeholders:**
```json
{
  "colors": {
    "surface": "gray-800",
    "foreground": "white", 
    "error": "red-500"
  },
  "components": {
    "good-example": "bg-{surface} text-{foreground} border-{error}"
  }
}
```

This ensures all colors are centralized in the `colors` section and can be easily changed project-wide.

## PHP Integration

### Reading Configuration

```php
// Get available colors for debugging
$colors = TailwindClassGenerator::getAvailableColors();

// Get available components
$components = TailwindClassGenerator::getAvailableComponents();
```

### Runtime Customization

```php
// Override or add components at runtime
TailwindClassGenerator::setComponent('custom-button', 'px-4 py-2 bg-blue-500 text-white');

// Override or add colors
TailwindClassGenerator::setColor('brand', 'purple-600');
```

## Best Practices

### Template Semantics

```smarty
{* ✅ Good - Semantic and meaningful *}
<button class="{tw component='button-primary'}">Submit</button>
<div class="{tw component='card'}">Content</div>

{* ❌ Avoid - Still hardcoded Tailwind *}
<button class="bg-red-600 text-white px-4 py-2">Submit</button>
```

### Component Organization

```json
{
  "components": {
    // Group by purpose
    "button-primary": "...",
    "button-secondary": "...",
    "button-outline": "...",
    
    "card": "...",
    "card-hover": "...",
    
    "text-heading": "...",
    "text-muted": "..."
  }
}
```

### Color Definition Rules

**1. Use Tailwind Color Names (Preferred)**
```json
{
  "colors": {
    "primary": "red-600",        // Standard Tailwind color
    "secondary": "gray-600",     // Standard Tailwind color
    "surface": "gray-100",       // Background surfaces
    "foreground": "gray-900",    // Primary text
    "muted": "gray-500"          // Secondary text
  }
}
```

**2. For Custom Brand Colors, Use Bracket Notation**
```json
{
  "colors": {
    "brand-primary": "[#f5d561]",     // Custom hex color
    "brand-accent": "[#1a202c]"       // Custom hex color
  }
}
```

**3. For CSS Variable References**
```json
{
  "colors": {
    "primary": "[color:var(--color-primary)]",    // Reference CSS custom property
    "surface": "[color:var(--color-surface)]"     // Reference CSS custom property
  }
}
```

**❌ Never use raw hex codes without brackets:**
```json
{
  "colors": {
    "primary": "#f5d561"  // WRONG - will not work
  }
}
```

## Tailwind JIT Configuration

For proper class generation, ensure your `tailwind.config.js` includes the configuration file:

```javascript
module.exports = {
  content: [
    './src/**/*.{php,tpl,ts,js}',
    './src/css/tailwind.json',  // Include your semantic config
    './lib/tn-framework/src/**/*.{php,tpl,ts,js}'
  ],
  // Add classes that might not be detected by JIT scanner
  safelist: [
    'fixed', 'bottom-4', 'right-4', 'z-50',  // Common positioning classes
    // Add other classes that are dynamically generated
  ]
}
```

## Common Pitfalls

### 1. JSON Formatting
- **Preserve whitespace** when editing JSON files
- Use consistent indentation (2 or 4 spaces)
- Avoid reformatting that changes existing structure

### 2. Color Reference Errors
- Don't mix color definition styles within the same project
- Always test color references after changes
- Use browser dev tools to verify generated classes

### 3. Component Abstraction
- Start with hardcoded Tailwind classes in templates
- Gradually extract common patterns into `tailwind.json` components
- Don't over-abstract - some one-off styling is acceptable

## Framework Notes

- The `TailwindClassGenerator` class is optional and only loaded if Tailwind is used
- The configuration file path is relative to `$_ENV['TN_ROOT']`
- All Smarty functions gracefully degrade if the class isn't available
- The system supports both light and dark mode through standard Tailwind classes
- Components can be extended or overridden at runtime for customization

This integration maintains template readability while providing the flexibility and performance benefits of Tailwind CSS, with a clear separation between design tokens and implementation details.
