# Package System

## Overview

The TN Framework uses a unique package system that allows for modular code organization and feature overriding through a stack-based approach. This system enables clean separation of concerns and easy extensibility.

## Package Structure

### Basic Structure
```
MyPackage/
├── Module/
│   └── MyModule/
│       ├── Module.php
│       ├── Component/
│       └── Controller/
└── Package.php
```

### Naming Conventions

- Package names should be 2-5 letter/number abbreviations
- Base package is always `TN`
- Additional packages extend the base package (e.g., `VSC` for VSCode)
- Module names follow the format: `{PACKAGE_NAME}_{MODULE_NAME}`

## Package Stack

### Configuration

- Package order is defined in the `.env` file
- Creates a stack where higher packages can override lower packages
- Base package (`TN`) is typically the lowest in the stack
- Example stack order:
  ```
  PACKAGE_STACK=VSC,TN
  ```

### Override System

1. Higher packages can override any aspect of lower packages
2. Override by placing files in the same relative path
3. Always edit files in the highest overriding package

## Modules

### Module Definition

Each module must have a `Module.php` file that extends the base Module class:

```php
namespace TN\TN_Billing;

class Module extends \TN\TN_Core\Model\Package\Module
{
    public string $package = \TN\Package::class;
    public string $name = 'TN_Billing';
}
```

### Module Organization

1. Components
   - Located in `Component/` directory
   - Grouped by controller or functionality
   - Can be overridden by higher packages

2. Controllers
   - Located in `Controller/` directory
   - Define routes and component mappings
   - Can be extended or overridden

3. Models
   - Located in `Model/` directory
   - Contains business logic
   - Can be extended in higher packages

## Best Practices

### Package Development

1. **Package Creation**
   - Use meaningful abbreviations
   - Document package purpose and dependencies

2. **Module Organization**
   - Group related functionality
   - Keep modules focused and cohesive
   - Use clear, descriptive names

3. **Override Management**
   - Track which files are overridden
   - Document override reasons
   - Maintain override consistency

### File Organization

1. **Component Files**
   ```
   {PACKAGE}/{MODULE}/Component/{CONTROLLER}/{ComponentName}/
   ├── ComponentName.php
   ├── ComponentName.tpl
   ├── ComponentName.ts
   └── _ComponentName.scss
   ```

2. **Controller Files**
   ```
   {PACKAGE}/{MODULE}/Controller/
   └── ControllerName.php
   ```

3. **Model Files**
   ```
   {PACKAGE}/{MODULE}/Model/
   └── ModelName.php
   ```

## Package Development Workflow

1. **Creating a New Package**
   ```
   MyPackage/
   ├── Module/
   ├── Package.php
   └── composer.json
   ```

2. **Adding a Module**
   ```
   MyPackage/Module/MyModule/
   ├── Module.php
   ├── Component/
   ├── Controller/
   └── Model/
   ```

3. **Overriding Features**
   - Identify target file in lower package
   - Create same file structure in higher package
   - Copy and modify as needed
   - Test override functionality

## Common Use Cases

### 1. Feature Extension
```php
// Lower package (TN)
class BaseComponent extends HTMLComponent {
    public function prepare(): void {
        // Base functionality
    }
}

// Higher package (VSC)
class BaseComponent extends \TN\TN_Core\Component\BaseComponent {
    public function prepare(): void {
        parent::prepare();
        // Additional functionality
    }
}
```

### 2. Template Override
```
// Original template in TN
TN/TN_Core/Component/Header/Header.tpl

// Override in VSC
VSC/TN_Core/Component/Header/Header.tpl
```

### 3. Controller Extension
```php
// Add new routes to existing controller
class UserController extends \TN\TN_User\Controller\UserController {
    #[Path('custom-route')]
    public function customAction(): void {
        // New functionality
    }
}
```

## Troubleshooting

### Common Issues

1. **Override Not Working**
   - Check package stack order
   - Verify file paths match exactly
   - Clear cache if necessary

2. **Module Not Loading**
   - Verify Module.php exists
   - Check namespace declarations
   - Validate package configuration

3. **Component Conflicts**
   - Check for naming collisions
   - Verify override hierarchy
   - Review package dependencies

## Package Maintenance

### Version Control

- Keep package changes isolated
- Use meaningful commit messages
- Document breaking changes
- Track override dependencies

### Documentation

- Maintain package-specific documentation
- Document override decisions
- Keep dependency lists updated
- Include upgrade guides