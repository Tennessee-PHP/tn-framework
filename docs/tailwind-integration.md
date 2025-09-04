# Tailwind CSS Integration (Optional)

The TN Framework provides optional Tailwind CSS integration through a semantic design system. This allows projects to define design tokens centrally and use semantic class names in templates that automatically adapt to light/dark themes.

## Overview

If your project uses Tailwind CSS, the framework provides:
- **Semantic Design Tokens**: Define colors centrally using CSS variables and Tailwind v4's `@theme` directive
- **Template Abstraction**: Use meaningful names like "primary" instead of "red-600"
- **Automatic Theme Switching**: Light/dark mode support without `dark:` variants in templates
- **Single Source of Truth**: All design decisions centralized and maintainable
- **Runtime Translation**: PHP converts semantic names to actual Tailwind classes

## Setup

### 1. Define Theme Variables in CSS

Create theme variables in `src/css/index.css` using Tailwind v4's `@theme` directive with a two-layer CSS variable system:

```css
@import "tailwindcss";

/* CSS Variables for dynamic theming */
:root {
  /* Primary - Main brand color for buttons, links, highlights */
  --theme-primary: theme('colors.yellow.500');                    /* Primary buttons, active states */
  --theme-primary-hover: theme('colors.yellow.600');              /* Primary button hover */
  --theme-primary-foreground: theme('colors.black');              /* Text on primary background */

  /* Secondary - Less prominent actions and elements */
  --theme-secondary: theme('colors.gray.600');                    /* Secondary buttons */
  --theme-secondary-hover: theme('colors.gray.700');              /* Secondary button hover */
  --theme-secondary-foreground: theme('colors.white');            /* Text on secondary background */

  /* Surfaces - Background colors for different UI layers */
  --theme-surface: theme('colors.white');                         /* Main content backgrounds */
  --theme-surface-secondary: theme('colors.gray.100');            /* Input fields, subtle backgrounds */
  --theme-surface-tertiary: theme('colors.gray.50');              /* Hover states, very subtle backgrounds */

  /* Text Colors */
  --theme-foreground: theme('colors.gray.900');                   /* Primary text color */
  --theme-muted: theme('colors.gray.500');                        /* Secondary text, captions */

  /* UI Elements */
  --theme-border: theme('colors.gray.200');                       /* Standard borders */
  --theme-accent: theme('colors.gray.100');                       /* Subtle accents, code backgrounds */

  /* Status Colors */
  --theme-success: theme('colors.green.600');                     /* Success messages, checkmarks */
  --theme-error: theme('colors.red.500');                         /* Error messages, warnings */
  --theme-warning: theme('colors.amber.500');                     /* Warning messages */
  --theme-info: theme('colors.blue.600');                         /* Info messages, links */

  /* Role Colors - for user roles, special statuses */
  --theme-role-admin: theme('colors.amber.400');                  /* Admin user styling */
}

.dark {
  /* Dark Theme Color Overrides - explicitly define all variables for clarity */
  --theme-surface: theme('colors.gray.800');
  --theme-surface-secondary: theme('colors.gray.700');
  --theme-surface-tertiary: theme('colors.gray.800');
  --theme-foreground: theme('colors.white');
  --theme-muted: theme('colors.gray.400');
  --theme-border: theme('colors.gray.700');
  --theme-accent: theme('colors.gray.700');
  --theme-info: theme('colors.blue.400');
  
  /* Primary, secondary, success, error, warning colors often stay the same in dark mode */
  /* Only override them here if they need to change for dark mode */
}

/* Single @theme block referencing the CSS variables for utility generation */
@theme {
  --color-primary: var(--theme-primary);
  --color-primary-hover: var(--theme-primary-hover);
  --color-primary-foreground: var(--theme-primary-foreground);
  --color-secondary: var(--theme-secondary);
  --color-secondary-hover: var(--theme-secondary-hover);
  --color-secondary-foreground: var(--theme-secondary-foreground);
  --color-surface: var(--theme-surface);
  --color-surface-secondary: var(--theme-surface-secondary);
  --color-surface-tertiary: var(--theme-surface-tertiary);
  --color-foreground: var(--theme-foreground);
  --color-muted: var(--theme-muted);
  --color-border: var(--theme-border);
  --color-accent: var(--theme-accent);
  --color-success: var(--theme-success);
  --color-error: var(--theme-error);
  --color-warning: var(--theme-warning);
  --color-info: var(--theme-info);
  --color-role-admin: var(--theme-role-admin);
}
```

### 2. Create Semantic Component Configuration

Create `src/css/tailwind.json` in your project with **ONLY reusable components**. Here's the comprehensive list based on our real-world implementation:

```json
{
  "components": {
    // ===== BUTTONS & ACTIONS =====
    "button-primary": "inline-flex items-center px-4 py-2 bg-primary text-primary-foreground font-medium rounded-lg hover:bg-primary-hover transition-colors",
    "button-secondary": "inline-flex items-center px-4 py-2 bg-secondary text-secondary-foreground font-medium rounded-lg hover:bg-secondary-hover transition-colors",
    "button-outline": "inline-flex items-center px-4 py-2 border border-border text-foreground font-medium rounded-lg hover:bg-surface-tertiary transition-colors",
    "button-ghost": "inline-flex items-center px-4 py-2 text-foreground font-medium rounded-lg hover:bg-surface-tertiary transition-colors",
    "reaction-button": "flex items-center space-x-1 px-2 py-1 rounded bg-surface-secondary hover:bg-border transition-colors text-sm",

    // ===== CARDS & CONTENT CONTAINERS =====
    "card": "bg-surface rounded-lg border border-border overflow-hidden hover:border-primary/50 transition-colors",
    "card-basic": "bg-surface rounded-lg border border-border overflow-hidden",
    "card-with-dividers": "bg-surface rounded-lg border border-border overflow-hidden hover:border-primary/50 transition-colors divide-y divide-border",
    "surface": "bg-surface-secondary rounded px-2 py-1",

    // ===== FORMS & INPUTS =====
    "input": "block w-full rounded-md bg-surface-secondary border border-border px-3 py-2.5 text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent",
    "input-error": "block w-full rounded-md bg-surface-secondary border border-error px-3 py-2.5 text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-error focus:border-transparent",
    
    "form-container": "flex flex-col justify-center px-6 py-12 lg:px-8 bg-surface-secondary",
    "form-header": "sm:mx-auto sm:w-full sm:max-w-sm",
    "form-header-wide": "sm:mx-auto sm:w-full sm:max-w-md",
    "form-title": "mt-10 text-center text-3xl font-bold tracking-tight text-foreground font-sans",
    "form-subtitle": "mt-2 text-center text-muted font-sans",
    "form-body": "mt-10 sm:mx-auto sm:w-full sm:max-w-sm",
    "form-body-wide": "mt-10 sm:mx-auto sm:w-full sm:max-w-md",
    "form-fields": "space-y-6",
    "form-field-group": "grid grid-cols-1 gap-4 sm:grid-cols-2",
    "form-label": "block text-sm font-medium text-foreground font-sans",
    "form-label-note": "text-xs text-muted font-sans",
    "form-input-wrapper": "mt-2",
    "form-input": "block w-full rounded-md bg-surface-tertiary border border-border px-3 py-2.5 text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent font-sans",
    "form-submit": "flex w-full justify-center rounded-md bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90 transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface-secondary font-sans cursor-pointer",
    "form-error": "rounded-md bg-error-surface border border-error p-4",
    "form-error-text": "text-sm text-error font-sans",
    "form-success": "rounded-md bg-success-surface border border-success p-4",
    "form-success-text": "text-sm text-success font-sans",
    "form-footer-text": "mt-6 text-center text-sm text-muted font-sans",
    "form-footer-link": "font-semibold text-primary hover:opacity-80 transition-colors",
    "form-terms": "text-sm text-muted font-sans",

    // ===== NAVIGATION =====
    "nav-link": "inline-flex items-center gap-1 rounded-md px-2 py-2 text-sm font-medium text-muted hover:bg-surface-secondary hover:text-foreground transition-colors",
    "nav-link-active": "inline-flex items-center gap-1 rounded-md bg-muted px-2 py-2 text-sm font-medium text-surface",
    "nav-link-mobile": "flex items-center gap-1 rounded-md px-2 py-2 text-base font-medium text-muted hover:bg-surface-secondary hover:text-foreground transition-colors",
    "nav-link-mobile-active": "flex items-center gap-1 rounded-md bg-muted px-2 py-2 text-base font-medium text-surface",
    "nav-icon-button": "relative inline-flex items-center justify-center rounded-md p-2 text-muted hover:bg-surface-secondary hover:text-foreground focus:outline-2 focus:-outline-offset-1 focus:outline-primary transition-colors",
    "nav-button": "relative rounded-md p-2 text-muted hover:bg-surface-secondary hover:text-foreground focus:outline-2 focus:outline-offset-2 focus:outline-primary transition-colors",
    "nav-avatar": "size-8 rounded-full bg-primary flex items-center justify-center",
    "nav-avatar-mobile": "size-10 rounded-full bg-primary flex items-center justify-center",
    "nav-dropdown-trigger": "inline-flex items-center gap-1 rounded-md px-2 py-2 text-sm font-medium text-muted hover:bg-surface-secondary hover:text-foreground transition-colors",
    "nav-dropdown-trigger-active": "inline-flex items-center gap-1 rounded-md bg-surface-secondary px-2 py-2 text-sm font-medium text-foreground",

    // ===== LAYOUT & CONTAINERS =====
    "container": "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
    "nav-container": "mx-auto max-w-7xl px-2 sm:px-6 lg:px-8",
    "navbar": "fixed top-0 left-0 right-0 z-50 bg-surface/80 backdrop-blur-sm border-b border-muted/20 after:pointer-events-none after:absolute after:inset-x-0 after:bottom-0 after:h-px after:bg-white/10",
    "heading": "text-2xl font-bold text-foreground sm:text-3xl",
    "subheading": "mt-2 text-sm text-muted",

    // ===== DROPDOWNS & MENUS =====
    "dropdown-menu": "w-48 origin-top-right rounded-md bg-surface py-1 ring-1 ring-black/5 transition transition-discrete [--anchor-gap:--spacing(2)] data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in",
    "dropdown-menu-wide": "w-56 origin-top-right rounded-md bg-surface py-1 ring-1 ring-black/5 transition transition-discrete [--anchor-gap:--spacing(2)] data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in",
    "dropdown-item": "block px-4 py-2 text-sm text-muted focus:bg-surface-secondary focus:outline-hidden",
    "dropdown-header": "block px-4 py-2 text-sm font-semibold text-foreground",

    // ===== MOBILE NAVIGATION =====
    "mobile-section-header": "px-2 py-2 text-xs font-semibold text-muted uppercase tracking-wider",
    "mobile-section-toggle": "flex items-center justify-between w-full px-2 py-2 text-xs font-semibold text-muted uppercase tracking-wider hover:bg-surface-secondary transition-colors cursor-pointer",
    "nav-link-mobile-toggle": "flex items-center justify-between w-full rounded-md px-2 py-2 text-base font-medium text-muted hover:bg-surface-secondary hover:text-foreground transition-colors cursor-pointer",
    "mobile-section-content": "space-y-1 transition-all duration-200 ease-in-out overflow-hidden",
    "mobile-profile-item": "block px-4 py-2 text-base font-medium text-muted hover:bg-surface-secondary hover:text-foreground transition-colors",

    // ===== MODALS & DIALOGS =====
    "modal-backdrop": "absolute inset-0 bg-muted/75 transition-opacity duration-500 ease-in-out data-closed:opacity-0",
    "modal-panel": "group/dialog-panel relative ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700",
    "modal-close-button": "relative rounded-md text-muted hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-info transition-colors",
    "modal-content": "relative flex h-full flex-col overflow-y-auto bg-surface py-6",
    "modal-header": "px-4 sm:px-6",
    "modal-title": "text-base font-semibold text-foreground flex items-center gap-2",
    "modal-subtitle": "text-sm text-muted mt-1",
    "modal-body": "relative mt-6 flex-1 px-4 sm:px-6 space-y-6",
    "modal-dialog": "fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent not-open:hidden backdrop:bg-transparent",

    // ===== PAGINATION =====
    "pagination-container": "sticky bottom-0 z-50 bg-surface/80 border-t border-border/80 backdrop-blur-sm flex items-center justify-between px-4 py-3 sm:px-6 mt-8",
    "pagination-mobile-prev": "relative inline-flex items-center rounded-md border border-border bg-surface px-4 py-2 text-sm font-medium text-foreground hover:bg-surface-tertiary transition-colors",
    "pagination-mobile-next": "relative ml-3 inline-flex items-center rounded-md border border-border bg-surface px-4 py-2 text-sm font-medium text-foreground hover:bg-surface-tertiary transition-colors",
    "pagination-info": "text-sm text-muted",
    "pagination-nav": "isolate inline-flex -space-x-px rounded-md",
    "pagination-prev": "relative inline-flex items-center rounded-l-md px-2 py-2 text-foreground ring-1 ring-inset ring-border hover:bg-surface-tertiary focus:z-20 focus:outline-offset-0 transition-colors",
    "pagination-next": "relative inline-flex items-center rounded-r-md px-2 py-2 text-foreground ring-1 ring-inset ring-border hover:bg-surface-tertiary focus:z-20 focus:outline-offset-0 transition-colors",
    "pagination-ellipsis": "relative inline-flex items-center px-4 py-2 text-sm font-semibold text-muted ring-1 ring-inset ring-border focus:outline-offset-0",
    "pagination-active": "relative z-10 inline-flex items-center bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground focus:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary",
    "pagination-inactive": "relative inline-flex items-center px-4 py-2 text-sm font-semibold text-foreground ring-1 ring-inset ring-border hover:bg-surface-tertiary focus:z-20 focus:outline-offset-0 transition-colors",
    "pagination-disabled": "opacity-50 pointer-events-none",

    // ===== BREADCRUMBS =====
    "breadcrumb-nav": "flex",
    "breadcrumb-list": "flex space-x-4 rounded-md bg-surface-secondary/50 px-6 outline -outline-offset-1 outline-border/50",
    "breadcrumb-item": "flex",
    "breadcrumb-item-content": "flex items-center",
    "breadcrumb-home-link": "text-foreground hover:text-primary transition-colors",
    "breadcrumb-chevron": "h-full w-6 shrink-0 text-muted",
    "breadcrumb-link": "ml-4 text-sm font-medium text-foreground hover:text-primary transition-colors",
    "breadcrumb-current": "ml-4 text-sm font-medium text-foreground",

    // ===== TOASTS & NOTIFICATIONS =====
    "toast-container": "fixed bottom-0 left-1/2 transform -translate-x-1/2 p-3 z-50 flex flex-col gap-2",
    "toast-base": "bg-surface border border-border rounded-lg max-w-sm w-full transform transition-all duration-300 ease-in-out opacity-0 scale-95",
    "toast-success": "bg-success-surface border-success-border text-success",
    "toast-error": "bg-error-surface border-error-border text-error",
    "toast-header": "flex items-center justify-between p-4 pb-2",
    "toast-title": "flex items-center gap-2 font-semibold text-sm",
    "toast-close": "text-muted hover:text-secondary transition-colors cursor-pointer",
    "toast-body": "px-4 pb-4 text-sm",

    // ===== EMPTY STATES =====
    "empty-state": "text-center py-12",
    "empty-state-icon": "text-5xl text-muted block mx-auto",
    "empty-state-title": "mt-2 text-sm font-semibold text-foreground",
    "empty-state-description": "mt-1 text-sm text-muted",

    // ===== CONTROLS & FILTERS =====
    "controls-container": "sticky top-16 z-40 bg-surface/80 backdrop-blur-sm px-4 py-3",
    "controls-layout": "flex items-center justify-between gap-4",
    "sort-label": "text-sm font-medium text-muted",
    "sort-button-group": "isolate inline-flex rounded-md",
    "sort-button-base": "relative inline-flex items-center px-3 py-2 text-sm font-medium border border-border focus:z-10 cursor-pointer transition-colors",
    "sort-button-left": "rounded-l-md",
    "sort-button-right": "rounded-r-md -ml-px",
    "sort-button-active": "bg-primary text-primary-foreground border-primary",
    "sort-button-inactive": "bg-surface text-foreground hover:bg-surface-tertiary",
    
    "filter-button": "inline-flex items-center gap-x-1 text-sm font-medium text-foreground px-3 py-2 rounded-md bg-surface border border-border hover:bg-surface-tertiary transition-colors cursor-pointer",
    "filter-button-base": "px-3 py-2 text-sm font-medium rounded-lg transition-colors",
    "filter-button-active": "bg-primary text-primary-foreground",
    "filter-button-inactive": "border border-border text-foreground hover:bg-surface-tertiary transition-colors",
    "filter-badge": "inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-primary-foreground bg-primary rounded-full ml-1",
    "filter-popover": "w-screen max-w-min overflow-visible bg-transparent px-4 transition transition-discrete [--anchor-gap:--spacing(5)] backdrop:bg-transparent open:flex data-closed:translate-y-1 data-closed:opacity-0 data-enter:duration-200 data-enter:ease-out data-leave:duration-150 data-leave:ease-in",
    "filter-content": "w-80 shrink rounded-lg bg-surface border border-border p-6 text-sm",
    "filter-label": "block text-sm font-medium text-foreground mb-2",
    "filter-input": "block w-full rounded-md bg-surface-tertiary border border-border px-3 py-2.5 text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm",
    "filter-actions": "flex items-center justify-between mt-6 pt-4 border-t border-border",
    "filter-reset": "text-sm text-muted hover:text-foreground transition-colors cursor-pointer",
    "filter-apply": "inline-flex items-center px-4 py-2 bg-primary text-primary-foreground font-medium rounded-lg hover:bg-primary-hover transition-colors text-sm cursor-pointer",

    // ===== FOOTER =====
    "footer": "bg-surface-secondary py-8",
    "footer-content": "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
    "footer-text": "text-foreground font-sans text-sm opacity-90",

    // ===== UTILITY COMPONENTS =====
    "image-placeholder": "absolute inset-0 flex items-center justify-center text-muted",
    "overlay-title": "text-base font-bold text-white bg-black/80 px-3 py-2 rounded relative z-10 group-hover:bg-black/90 transition-colors",
    "image-hover": "absolute inset-0 bg-cover bg-center group-hover:scale-105 transition-transform duration-300",
    "username": "font-semibold hover:opacity-80 cursor-pointer text-xs",
    "profile-image-small": "size-8 rounded-full object-cover",
    "profile-image-medium": "size-10 rounded-full object-cover",
    "loading-spinner": "material-symbols-outlined animate-spin -ml-1 mr-3 text-lg",
    "load-more-trigger": "flex items-center justify-center py-8 text-muted",
    "load-more-spinner": "flex items-center space-x-2 text-secondary",
    "password-strength-container": "password-strength mt-2",

    // ===== THEME SWITCHER =====
    "theme-switcher": "grid grid-cols-3 gap-0.5 rounded-full bg-surface-secondary p-1 text-foreground",
    "theme-option": "relative rounded-full p-2 cursor-pointer hover:bg-border transition-colors flex items-center justify-center",
    "theme-option-active": "bg-surface ring-1 ring-border",
    "theme-option-input": "absolute inset-0 appearance-none"
  },
  
  "sizes": {
    "button": {
      "small": "px-3 py-1.5 text-sm",
      "medium": "px-4 py-2 text-sm", 
      "large": "px-6 py-3 text-base"
    },
    "input": {
      "small": "px-2 py-1.5 text-sm",
      "medium": "px-3 py-2.5 text-sm",
      "large": "px-4 py-3 text-base"
    },
    "spacing": {
      "tight": "space-x-2",
      "medium": "space-x-3", 
      "spaced": "space-x-4"
    }
  }
}
```

### Component Categories Explained

**Buttons & Actions**: All interactive elements that trigger actions
**Cards & Content**: Reusable content containers and surfaces
**Forms & Inputs**: Complete form system with validation states
**Navigation**: Desktop and mobile navigation patterns
**Layout & Containers**: Page structure and content organization
**Dropdowns & Menus**: Interactive menu systems
**Modals & Dialogs**: Overlay UI patterns
**Pagination**: Data navigation controls
**Breadcrumbs**: Navigation hierarchy display
**Toasts & Notifications**: User feedback systems
**Empty States**: Placeholder content for empty data
**Controls & Filters**: Data manipulation interfaces
**Footer**: Site-wide footer components
**Utility Components**: Reusable UI utilities and helpers
**Theme Switcher**: Dark/light mode controls

### 3. Register Smarty Functions

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

{* Direct semantic color usage *}
<p class="{tw text_color='text-muted'}">Secondary text</p>
<div class="bg-surface border-border">Semantic background and border</div>
```

### Component with Overrides

```smarty
{* Add custom classes or overrides *}
<button class="{tw component='button-primary'} gap-2 text-lg">
  <span class="material-symbols-outlined">save</span>
  Save Changes
</button>

{* Size variants *}
<button class="{tw component='button-primary' size='large'}">Large Button</button>
```

### Conditional Classes

```smarty
{* Conditional styling using if/else in templates *}
<a href="?filter=newest" class="{if $activeFilter == 'newest'}{tw component='nav-link-active'}{else}{tw component='nav-link'}{/if}">
  Newest
</a>
```

## Critical Rules and Best Practices

### ❌ NEVER Use These Patterns

**1. Hardcoded Colors in Components:**
```json
{
  "components": {
    "bad-example": "bg-gray-800 dark:bg-gray-900 text-white border-red-500"
  }
}
```

**2. Dark Variants in Components:**
```json
{
  "components": {
    "bad-example": "bg-surface dark:bg-gray-800 text-foreground dark:text-white"
  }
}
```

**3. Hardcoded Colors in Templates:**
```smarty
{* ❌ WRONG *}
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
  Content
</div>
```

### ✅ ALWAYS Use These Patterns

**1. Semantic Colors Only in Components:**
```json
{
  "components": {
    "good-example": "bg-surface text-foreground border-error hover:bg-surface-tertiary"
  }
}
```

**2. Semantic Colors in Templates:**
```smarty
{* ✅ CORRECT *}
<div class="bg-surface text-foreground">
  Content
</div>

{* ✅ CORRECT - Using tw helper *}
<p class="{tw text_color='text-muted'}">Secondary text</p>
```

**3. CSS Variables for Dynamic Styles:**
```css
/* ✅ CORRECT - For custom CSS that needs theming */
.comment-content blockquote {
  border-left: 4px solid var(--color-primary);
  background-color: var(--color-surface-tertiary);
  color: var(--color-muted);
}
```

## Common Mistakes We Made (And How to Avoid Them)

### 1. **Tailwind JIT Not Detecting Semantic Classes**

**Problem:** JIT compiler doesn't generate CSS for `bg-primary` because it doesn't see literal class strings.

**❌ Wrong Solution:** Adding `tailwind.json` to Tailwind config content paths (JSON doesn't contain literal classes).

**✅ Correct Solution:** Use Tailwind v4's `@theme` directive in CSS - this automatically generates utility classes for all defined color variables.

### 2. **Invalid Nested @theme Blocks**

**Problem:** We initially tried this invalid structure:
```css
@theme {
  --color-surface: #ffffff;
}

.dark {
  @theme {  /* ❌ INVALID - @theme blocks must be top-level */
    --color-surface: #1f2937;
  }
}
```

**✅ Correct Solution:** Two-layer CSS variable system:
```css
:root { --theme-surface: theme('colors.white'); }
.dark { --theme-surface: theme('colors.gray.800'); }
@theme { --color-surface: var(--theme-surface); }
```

### 3. **Hardcoded Colors in Templates Breaking Theming**

**Problem:** Page body using `bg-white dark:bg-gray-900` instead of semantic colors.

**Symptoms:** Main page background doesn't adapt to theme changes.

**✅ Solution:** Replace with semantic colors:
```smarty
{* Before *}
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">

{* After *}
<body class="bg-surface text-foreground">
```

### 4. **Over-Abstracting Components**

**Problem:** Creating components for every single class list, even one-time use.

**Result:** Bloated `tailwind.json` with 200+ components, most used only once.

**✅ Solution:** Only create components that are:
- Used in multiple templates
- Represent reusable UI patterns (buttons, cards, navigation)
- Worth maintaining as a design system component

### 5. **Removing Components Without Updating Templates**

**Problem:** Removed 47 components from `tailwind.json` but forgot to update templates using them.

**Symptoms:** Broken layouts, missing styles, components rendering incorrectly.

**✅ Solution:** Systematic approach:
1. List all removed components
2. Search for every usage: `grep -r "screenshot-overlay\|layout-card-grid" src/`
3. Replace with actual class lists
4. Test each page/component

### 6. **Mixing Hardcoded and Semantic Colors**

**Problem:** Some components using semantic colors, others using hardcoded values.

**Result:** Inconsistent theming - some elements adapt to dark mode, others don't.

**✅ Solution:** Audit all hardcoded colors:
```bash
grep -r "bg-white\|bg-gray-\|text-gray-" src/ --include="*.tpl"
```

### 7. **Forgetting Transparency/Backdrop Effects**

**Problem:** Converting `bg-white/80 dark:bg-gray-900/80` to `bg-surface` loses transparency.

**✅ Solution:** Keep transparency with semantic colors:
```json
"navbar": "bg-surface/80 backdrop-blur-sm"
"controls-container": "bg-surface/80 backdrop-blur-sm"
```

## Component Organization Strategy

### Universal Components (Keep in tailwind.json)
- **Buttons:** `button-primary`, `button-secondary`, `button-outline`, `button-ghost`
- **Cards:** `card`, `card-basic`, `card-with-dividers`
- **Navigation:** `nav-link`, `nav-link-active`, `dropdown-menu`
- **Forms:** `input`, `input-error`, `form-submit`
- **Layout:** `container`, `heading`, `subheading`
- **Feedback:** `toast-success`, `toast-error`, `modal-*`

### Single-Use Components (Move to Templates)
- **Page-specific layouts:** Screenshot grids, object lists
- **Debug components:** Performance metrics, debug modals
- **One-off styling:** Specific page elements, unique layouts

## Tailwind Configuration

Ensure your `tailwind.config.js` is configured for optimal JIT detection:

```javascript
module.exports = {
  content: [
    './src/**/*.{php,tpl,ts,js}',
    './lib/tn-framework/src/**/*.{php,tpl,ts,js}'
    // Note: Don't include tailwind.json - it doesn't contain literal classes
  ],
  // Safelist classes that are dynamically generated or hard to detect
  safelist: [
    'fixed', 'bottom-4', 'right-4', 'z-50',
    // Add other dynamic classes if needed
  ]
}
```

## Testing Your Implementation

### 1. Theme Switching Test
- Toggle between light and dark modes
- Verify ALL elements adapt (page background, cards, text, borders)
- Check for any hardcoded colors that don't change

### 2. Component Consistency Test
- Ensure similar components look identical across pages
- Check that semantic colors resolve correctly in browser dev tools
- Verify hover states and interactions work in both themes

### 3. Build Process Test
- Ensure all semantic colors generate corresponding Tailwind utilities
- Check that JIT compiler includes all necessary classes in final CSS
- Verify no broken component references remain

## Migration Checklist

When implementing this system in an existing project:

- [ ] Set up CSS theme variables with two-layer system
- [ ] Create `tailwind.json` with universal components only  
- [ ] Audit templates for hardcoded colors
- [ ] Replace hardcoded colors with semantic equivalents
- [ ] Test theme switching on all major pages
- [ ] Remove unused components from `tailwind.json`
- [ ] Update any custom CSS to use `var(--color-*)` references
- [ ] Document your semantic color meanings for the team

This approach provides a maintainable, scalable design system that automatically handles theming while keeping templates clean and semantic.