# PHP Packages and Modules

## Overview

The TN Framework uses a package and module system for organizing code hierarchically. Packages contain modules, and higher packages in the stack can override lower packages.

## Package Structure

### Basic Package
```php
<?php
namespace TN;

class Package extends \TN\TN_Core\Model\Package\Package
{
    public string $name = 'TN';
    public array $modules = [
        \TN\TN_Core\Module::class,
        \TN\TN_Billing\Module::class
    ];
}
```

### Directory Structure
```
PackageName/
├── Package.php         # Package definition
├── Module1/
│   ├── Module.php     # Module definition  
│   ├── Controller/    # Route handlers
│   ├── Component/     # UI components
│   ├── Model/         # Business logic
│   └── Command/       # CLI commands
└── Module2/
    └── ...
```

## Module Structure

### Basic Module
```php
<?php
namespace Package\Module_Name;

class Module extends \TN\TN_Core\Model\Package\Module
{
    public string $package = \Package\Package::class;
    public string $name = 'Module_Name';
    public array $moduleDependencies = [
        \TN\TN_Core\Module::class
    ];
}
```

### Module Dependencies
Modules can depend on other modules:

```php
public array $moduleDependencies = [
    \TN\TN_Core\Module::class,
    \Package\Package_Main\Module::class
];
```

## Package Stack

### Stack Configuration
The package stack is defined in `.env`:

```env
PACKAGE_STACK=MyProject,TN
```

- **Higher packages override lower packages**
- **Order matters** - left to right, highest to lowest priority
- **TN package** is typically the base/lowest package

### Override System
Higher packages can override lower packages by placing files in the same relative path:

```
# Lower package (TN)
TN/TN_Core/Component/LoginForm/LoginForm.php

# Higher package override (MyProject) 
MyProject/TN_Core/Component/LoginForm/LoginForm.php  # This version is used
```

### Class Resolution
When the framework looks for a class, it searches packages from highest to lowest priority until it finds the class.

## Naming Conventions

### Packages
- Use 2-5 letter abbreviations (e.g., `TN`, `OP`, `VSC`)
- Choose meaningful, memorable names

### Modules  
- Format: `{PACKAGE_NAME}_{MODULE_NAME}`
- Examples: `TN_Core`, `TN_Billing`, `OP_Main`

### Namespaces
- Packages: `PackageName\`
- Modules: `PackageName\ModuleName\`
- Components: `PackageName\ModuleName\Component\Controller\ComponentName\`

## Creating New Packages

1. **Create package directory** in your project root
2. **Create Package.php** with package definition
3. **Add to PACKAGE_STACK** in `.env` 
4. **Create modules** within the package

## Module Organization

### Standard Directories
- **Controller/**: Route handlers and controllers
- **Component/**: HTML, JSON, and CLI components
- **Model/**: Data models and business logic
- **Command/**: CLI commands and scheduled tasks

This system enables modular code organization, easy overriding of framework functionality, and clear separation of concerns across different parts of your application.
