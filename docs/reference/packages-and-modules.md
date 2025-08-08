# TN Framework: Package and Module System

## Overview

The TN Framework uses a sophisticated package and module system that enables modular code organization, feature overriding, and hierarchical dependency management. This system allows for clean separation of concerns, easy extensibility, and provides a foundation for building scalable applications with reusable components.

## Core Concepts

### Package Stack Architecture

The framework operates on a **stack-based approach** where packages are organized in a hierarchy. Higher packages in the stack can override or extend functionality from lower packages, enabling powerful customization capabilities without modifying the original source code.

- **Bottom-up Resolution**: When the framework looks for a class, it starts from the highest package in the stack and works downward
- **Override System**: Higher packages can completely replace files from lower packages by placing files in the same relative path
- **Extension System**: Higher packages can extend classes from lower packages, adding or modifying functionality

### Key Components

1. **Stack** (`TN\TN_Core\Model\Package\Stack`): Manages the package hierarchy and class resolution
2. **Package** (`TN\TN_Core\Model\Package\Package`): Top-level organizational unit containing modules
3. **Module** (`TN\TN_Core\Model\Package\Module`): Self-contained functional units within packages
4. **CodeContainer** (`TN\TN_Core\Model\Package\CodeContainer`): Base class providing common functionality

---

## Packages

### Package Definition

A Package is the highest-level organizational unit in the framework. Each package must have a `Package.php` file that extends the base Package class:

```php
<?php
namespace TN;

class Package extends \TN\TN_Core\Model\Package\Package
{
    public string $name = 'TN';
    public array $modules = [
        \TN\TN_Core\Module::class
    ];
}
```

### Package Structure

```
PackageName/
├── Package.php                 # Package definition
├── Module1/
│   ├── Module.php             # Module definition
│   ├── Controller/            # Route handlers
│   ├── Component/             # UI components
│   ├── Model/                 # Business logic
│   ├── Command/               # CLI commands
│   └── View/                  # Templates
├── Module2/
│   └── ...
└── ModuleN/
    └── ...
```

### Package Responsibilities

- **Module Registration**: Define which modules belong to the package
- **Namespace Management**: Provide namespace isolation (`PackageName\*`)
- **Class Resolution**: Resolve class names within the package hierarchy
- **Directory Management**: Provide file system access for contained modules

### Package Discovery

Packages are automatically discovered by scanning directories in `$_ENV['TN_PHP_ROOT']` for `Package.php` files. The framework instantiates all found packages during initialization.

### Package Stack Configuration

The package stack order is defined in the `.env` file using the `PACKAGE_STACK` variable:

```env
PACKAGE_STACK=VSC,FBG,TN
```

- Packages are listed from highest to lowest priority
- Higher packages can override lower packages
- Base package (`TN`) is typically the lowest in the stack
- Order affects class resolution and file overriding

### Naming Conventions

- **Package Names**: Use 2-5 letter/number abbreviations (e.g., `TN`, `FBG`, `VSC`)
- **Base Package**: Always `TN` for the framework core
- **Module Names**: Follow `{PACKAGE_NAME}_{MODULE_NAME}` format
- **Clear Abbreviations**: Choose meaningful, memorable abbreviations

---

## Modules

### Module Definition

A Module is a self-contained functional unit within a package. Each module must have a `Module.php` file:

```php
<?php
namespace FBG\FBG_Main;

class Module extends \TN\TN_Core\Model\Package\Module
{
    public string $package = \FBG\Package::class;
    public string $name = 'FBG_Main';
    public array $moduleDependencies = [
        \TN\TN_Core\Module::class
    ];
}
```

### Module Structure

Modules follow a standardized directory structure:

- **Controller/**: Route handlers and request processing
- **Component/**: Reusable UI components and widgets
- **Model/**: Business logic, data structures, and persistence
- **Command/**: CLI commands and scheduled tasks
- **View/**: Templates and static assets

### Module Dependencies

Modules can declare dependencies on other modules using the `$moduleDependencies` array:

```php
public array $moduleDependencies = [
    \TN\TN_Core\Module::class,
    \FBG\FBG_Main\Module::class,
    \FBG\FBG_NFL\Module::class
];
```

Dependencies ensure:
- Proper loading order
- Availability of required functionality
- Clear architectural boundaries

### Module Naming Convention

Modules follow the format: `{PACKAGE_NAME}_{MODULE_NAME}`

Examples:
- `TN_Core` - Core framework module
- `TN_Billing` - Billing functionality
- `FBG_Main` - Main Footballguys functionality
- `FBG_NFL` - NFL-specific features

---

## Class Resolution and Overriding

### How Class Resolution Works

The Stack class manages class resolution across the package hierarchy:

1. **Strip Package Prefix**: Remove package name from class if present
2. **Iterate Through Packages**: Check each package in the stack order
3. **First Match Wins**: Return the first resolved class found
4. **Namespace Construction**: Build full class name as `{PackageName}\{ClassName}`

```php
// Example: Looking for 'TN_Core\Model\User\User'
// 1. Check FBG\TN_Core\Model\User\User (if FBG package exists)
// 2. Check TN\TN_Core\Model\User\User (base implementation)
```

### Class Extension Example

The framework enables powerful class extension through the package system. Here's how the Footballguys User extends the TN Core User:

**Base Implementation** (`lib/tn-framework/src/TN/TN_Core/Model/User/User.php`):
```php
<?php
namespace TN\TN_Core\Model\User;

class User implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    // Core user functionality
    public function getPlan(): ?Plan
    {
        return Plan::getActiveUserPlan($this);
    }
    
    public function getRoles(): array
    {
        // Base role implementation
    }
}
```

**Extended Implementation** (`src/FBG/TN_Core/Model/User/User.php`):
```php
<?php
namespace FBG\TN_Core\Model\User;

use TN\TN_Core\Model\User\User as TNUser;

class User extends TNUser
{
    // Override: Staff get HOF access
    public function getPlan(): ?Plan
    {
        if ($this->hasRole('staffer')) {
            return Plan::getInstanceByKey('level30');
        }
        return parent::getPlan();
    }
    
    // Add: Discord integration
    public function setDiscordRoles(): void
    {
        $discordUser = DiscordUser::getFromTnUser($this);
        // ... Discord-specific logic
    }
    
    // Add: ConvertKit integration
    public function subscribeToMailingList(): void
    {
        Queue::subscribeToForm($this->email, 'users');
    }
    
    // Override with caching
    public function getRoles(): array
    {
        $cacheKey = $this->getStafferRolesCacheKey();
        $cachedRoles = Cache::get($cacheKey);
        if ($cachedRoles !== false) {
            return $cachedRoles;
        }
        return parent::getRoles();
    }
}
```

### Key Extension Patterns

1. **Override Methods**: Replace base functionality entirely
2. **Extend Methods**: Call parent method and add additional functionality
3. **Add Methods**: Introduce completely new functionality
4. **Add Properties**: Extend the class with new attributes

---

## Package Hierarchy Examples

### Framework Package (TN)

```php
// lib/tn-framework/src/TN/Package.php
namespace TN;

class Package extends \TN\TN_Core\Model\Package\Package
{
    public string $name = 'TN';
    public array $modules = [
        \TN\TN_Core\Module::class
    ];
}
```

### Application Package (FBG)

```php
// src/FBG/Package.php
namespace FBG;

class Package extends \TN\TN_Core\Model\Package\Package
{
    public string $name = 'FBG';
    public array $modules = [
        \FBG\FBG_Main\Module::class,
        \FBG\FBG_Contest\Module::class,
        \FBG\FBG_FF\Module::class,
        \FBG\FBG_NFL\Module::class,
        \FBG\FBG_Chat\Module::class,
        // Include framework modules
        \TN\TN_Core\Module::class,
        \TN\TN_CMS\Module::class,
        \TN\TN_Billing\Module::class,
        \TN\TN_Reporting\Module::class,
        \TN\TN_Advert\Module::class,
        \TN\TN_S3Download\Module::class
    ];
}
```

---

## Best Practices

### Creating New Packages

1. **Create Package Directory**: Create a new directory in the source root
2. **Define Package Class**: Create `Package.php` extending the base Package class
3. **Set Package Name**: Use clear, abbreviated naming (2-5 characters)
4. **Register Modules**: List all modules in the `$modules` array

### Creating New Modules

1. **Create Module Directory**: Create a directory within your package
2. **Define Module Class**: Create `Module.php` extending the base Module class
3. **Set Dependencies**: Declare required modules in `$moduleDependencies`
4. **Follow Naming Convention**: Use `{PACKAGE}_{MODULE}` format

### Extending Classes

1. **Use Proper Namespace**: Extend classes in your package's namespace
2. **Import Parent Class**: Use `use` statements with aliases when needed
3. **Call Parent Methods**: Use `parent::method()` when extending functionality
4. **Maintain Interface Compatibility**: Don't break existing method signatures

### Organizing Code

1. **Logical Grouping**: Group related functionality in the same module
2. **Clear Dependencies**: Keep module dependencies minimal and clear
3. **Single Responsibility**: Each module should have a focused purpose
4. **Namespace Alignment**: Keep PHP namespaces in sync with directory structure

### File Organization Patterns

#### Component Files
```
{PACKAGE}/{MODULE}/Component/{CONTROLLER}/{ComponentName}/
├── ComponentName.php      # Main component class
├── ComponentName.tpl      # Smarty template
├── ComponentName.ts       # TypeScript functionality
└── _ComponentName.scss    # Component styles
```

#### Controller Files
```
{PACKAGE}/{MODULE}/Controller/
└── ControllerName.php     # Route definitions and handlers
```

#### Model Files
```
{PACKAGE}/{MODULE}/Model/
├── EntityName/           # Group related entities
│   ├── EntityName.php   # Main entity class
│   └── SubEntity.php    # Related entities
└── StandaloneModel.php  # Independent models
```

---

## Development Workflow

### Creating a New Package

1. **Create Package Directory**: Create directory in source root
   ```
   MyPackage/
   ├── Module/
   ├── Package.php
   └── composer.json (if applicable)
   ```

2. **Define Package Class**: Extend base Package class
3. **Update Package Stack**: Add to `.env` file
4. **Register Modules**: List modules in `$modules` array

### Adding a Module

1. **Create Module Directory**:
   ```
   MyPackage/Module/MyModule/
   ├── Module.php
   ├── Component/
   ├── Controller/
   └── Model/
   ```

2. **Define Module Class**: Extend base Module class
3. **Set Dependencies**: Declare in `$moduleDependencies`
4. **Register Module**: Add to package's `$modules` array

### Overriding Features

1. **Identify Target**: Find file in lower package to override
2. **Match Structure**: Create same directory structure in higher package
3. **Copy and Modify**: Start with copy, then customize
4. **Test Override**: Verify functionality works as expected

---

## Common Use Cases

### Feature Extension

Extend base functionality while preserving original behavior:

```php
// Lower package (TN)
class BaseComponent extends HTMLComponent {
    public function prepare(): void {
        $this->data = ['basic' => 'data'];
    }
}

// Higher package (FBG)
class BaseComponent extends \TN\TN_Core\Component\BaseComponent {
    public function prepare(): void {
        parent::prepare();
        $this->data['extended'] = 'additional data';
    }
}
```

### Template Override

Replace templates with customized versions:

```
// Original template in TN
TN/TN_Core/Component/Header/Header.tpl

// Custom template in FBG  
FBG/TN_Core/Component/Header/Header.tpl
```

### Controller Extension

Add new routes to existing controllers:

```php
class UserController extends \TN\TN_User\Controller\UserController {
    #[Path('custom-profile')]
    public function customProfile(): void {
        // New functionality specific to this package
    }
}
```

---

## Troubleshooting

### Common Issues

#### Override Not Working
- **Check Stack Order**: Verify package order in `.env`
- **Verify Paths**: Ensure file paths match exactly
- **Clear Cache**: Framework may cache class resolution
- **Namespace Check**: Confirm namespace declarations are correct

#### Module Not Loading
- **Module.php Exists**: Verify `Module.php` file is present
- **Namespace Correct**: Check namespace matches directory structure
- **Package Registration**: Ensure module is listed in package's `$modules`
- **Dependencies Met**: Verify all `$moduleDependencies` are available

#### Component Conflicts
- **Name Collisions**: Check for duplicate component names
- **Override Hierarchy**: Review which package should take precedence
- **Dependencies**: Ensure component dependencies are properly declared

### Debugging Tips

1. **Enable Debug Mode**: Check class resolution in debug output
2. **Check Logs**: Framework logs class loading issues
3. **Verify Stack**: Use debugging tools to check package load order
4. **Test Isolation**: Test components individually to isolate issues

---

## Package Maintenance

### Version Control Best Practices

- **Isolate Changes**: Keep package modifications separate
- **Meaningful Commits**: Use clear, descriptive commit messages
- **Document Breaking Changes**: Note any backward compatibility issues
- **Track Dependencies**: Maintain documentation of package relationships

### Documentation Standards

- **Package README**: Document package purpose and setup
- **Override Documentation**: Explain why overrides were necessary
- **Dependency Lists**: Keep module dependencies current
- **Upgrade Guides**: Provide migration instructions for major changes

### Maintenance Guidelines

1. **Regular Review**: Periodically review override necessity
2. **Dependency Cleanup**: Remove unused module dependencies
3. **Performance Monitoring**: Watch for class resolution performance
4. **Update Documentation**: Keep docs in sync with code changes

---

## Technical Implementation

### Stack Class Methods

- `resolveClassName(string $className)`: Resolve a class name across packages
- `getClassesInPackageNamespaces(string $namespace)`: Find all classes in a namespace across packages
- `getClassesInModuleNamespaces(string $namespace)`: Find all classes in module namespaces
- `getChildClasses(string $className)`: Get all child classes with package overrides

### Package Class Methods

- `resolveClassName(string $className)`: Check if a class exists in this package
- `getClassesInNamespace(string $namespace)`: Get all classes in a sub-namespace
- `getDir()`: Get the package's file system directory

### Module Class Methods

- `getDir()`: Get the module's file system directory
- `getClassesInNamespace(string $namespace)`: Get all classes in a sub-namespace
- Automatic dependency resolution and loading

---

## Conclusion

The TN Framework's package and module system provides a powerful foundation for building scalable, maintainable applications. By leveraging the stack-based architecture, developers can:

- Build modular applications with clear boundaries
- Override and extend functionality without modifying source code
- Maintain clean separation of concerns
- Create reusable components across projects
- Implement proper dependency management

This system enables both the framework maintainers and application developers to work efficiently while maintaining code quality and architectural integrity. 