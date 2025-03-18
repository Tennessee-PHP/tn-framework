# TN Framework Architecture Overview

## Core Philosophy

TN Framework is built on three fundamental principles:

1. **Modularity Through Packages**: Everything is a package, allowing for clean overrides and extensions
2. **Component-Based Design**: Self-contained, reusable components as the building blocks
3. **Type Safety**: Leveraging PHP 8's type system for reliability and developer experience

## System Architecture

```
Project/
├── TN/                     # Base package (lowest in stack)
│   └── Module/
│       ├── TN_Core/       # Framework core
│       ├── TN_User/       # User management
│       └── TN_Auth/       # Authentication
├── MyPackage/             # Custom package (higher in stack)
│   └── Module/
│       └── MyModule/      # Custom functionality
└── .env                   # Package stack configuration
```

### Package Stack

The package system is the foundation of TN Framework's architecture. Packages are stacked in order, with higher packages able to override lower ones:

1. Each package can contain multiple modules
2. Modules in higher packages can override those in lower packages
3. The stack order is configured in `.env`
4. The base `TN` package provides core functionality
5. Custom packages extend or override base functionality

### Component Architecture

Components are self-contained units that combine:

```
UserProfile/              # Component directory
├── UserProfile.php      # Business logic
├── UserProfile.tpl      # Template
├── UserProfile.ts       # Client-side code
└── _UserProfile.scss    # Styles
```

Key aspects:
- Components handle their own data fetching
- Templates are scoped to their component
- TypeScript provides type-safe client-side code
- SCSS is scoped using BEM methodology

## Request Lifecycle

1. **HTTP Request**
   ```
   http://example.com/user/profile/123
   ```

2. **Routing (TN_Core)**
   ```php
   #[Path('user/profile/{userId}')]
   #[Component(UserProfile::class)]
   public function profile(): void {}
   ```

3. **Component Initialization**
   ```php
   class UserProfile extends HTMLComponent {
       #[FromPath] public string $userId;
   }
   ```

4. **Data Loading (prepare phase)**
   ```php
   public function prepare(): void {
       $this->user = User::readFromId($this->userId);
   }
   ```

5. **Template Rendering**
   ```smarty
   <div class="{$classAttribute}" id="{$idAttribute}">
       {$user->name|escape}
   </div>
   ```

6. **Client-side Initialization**
   ```typescript
   class UserProfile extends HTMLComponent {
       protected observe(): void {
           // Component initialization
       }
   }
   ```

## Data Layer

The framework uses `PersistentModel` trait and MySQL storage for data persistence:

```php
namespace TN\TN_Core\Model\User;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

#[TableName('users')]
class User implements Persistence {
    use MySQL;
    use PersistentModel;

    #[EmailAddress]
    public string $email;
    
    #[Impersistent]
    public string $password;
}
```

Features:
- MySQL persistence through trait
- Attribute-based configuration
- Built-in validation
- Automatic type enforcement

## Security Architecture

1. **Authentication**
   - Session-based auth by default
   - Support for JWT tokens
   - OAuth integration capability

2. **Authorization**
   - Role-based access control
   - Component-level permissions
   - Route protection

3. **Data Protection**
   - CSRF protection
   - XSS prevention
   - SQL injection prevention
   - Password hashing

## Frontend Architecture

1. **Asset Management**
   - Automatic bundling
   - Cache busting
   - Development hot reload
   - Production optimization

2. **TypeScript Integration**
   ```typescript
   import { HTMLComponent } from '@tn/core';
   
   export class UserProfile extends HTMLComponent {
       protected observe(): void {
           this.on('click', '.edit-button', this.handleEdit);
       }
   }
   ```

## Performance Optimizations

1. **Server-side**
   - Template caching
   - Database query optimization
   - Lazy loading of components
   - Route caching

2. **Client-side**
   - Asset minification
   - Selective component reloading
   - Efficient DOM updates
   - Resource prefetching

## Development Tools

1. **CLI Tools**
   ```bash
   php src/run.php components/map    # Update component maps
   ```

## AI Integration

TN Framework is designed to work well with AI coding assistants:

1. **Consistent Structure**
   - Predictable file locations
   - Standard naming conventions
   - Clear component boundaries

2. **Type Safety**
   - Full PHP type hints
   - TypeScript definitions
   - Clear interfaces

3. **Documentation**
   - Inline documentation
   - Clear examples
   - Structured guides 