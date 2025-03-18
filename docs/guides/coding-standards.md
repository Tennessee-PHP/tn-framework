# PHP Coding Standards

## Overview

These standards ensure consistent, maintainable, and high-quality PHP code across TN Framework projects. Following these standards is mandatory for all contributions.

## File Structure

```
src/
├── Domain/          # Business logic and entities
├── Application/     # Application services and use cases
├── Infrastructure/  # External services, repositories, frameworks
└── Interface/       # Controllers, CLI commands, API endpoints
```

## Naming Conventions

### Classes and Interfaces

- Use PascalCase
- One class per file
- Class name must match filename

```php
// UserRepository.php
class UserRepository {}

// AuthenticatorInterface.php
interface AuthenticatorInterface {}
```

### Methods and Properties

- Use camelCase
- Methods should be verbs/actions
- Properties should be nouns

```php
class User {
    private string $firstName;
    
    public function updateProfile(array $data): void {}
}
```

### Constants

- Use UPPER_SNAKE_CASE

```php
const MAX_LOGIN_ATTEMPTS = 3;
const API_VERSION = '1.0';
```

## Type System

- Always declare strict types at the beginning of each file:
  ```php
  declare(strict_types=1);
  ```
- Use type hints for parameters and return types
- Use nullable types with `?` prefix
- Return type declarations are mandatory

```php
class User extends PersistentModel {
    public function findByEmail(?string $email): ?static {
        return static::findOne([
            'email' => $email
        ]);
    }
    
    public function getActiveSubscriptions(): Collection {
        return Subscription::findAll([
            'userId' => $this->id,
            'status' => 'active'
        ]);
    }
}
```

## Code Style

- 4 spaces for indentation
- 120 character line limit
- Use short array syntax `[]`
- Single quotes for strings unless interpolating
- PSR-12 compliant
- Never use fully qualified class names in code
- Always use `use` statements at the top of files
- Place attributes and docblocks on the same line as property declarations

## Documentation

### PHPDoc Blocks

Required for:
- Classes
- Methods
- Properties with complex types
- Interface methods

Example:
```php
/**
 * Authenticates a user with the given credentials
 *
 * @param string $email User's email address
 * @param string $password Plain text password
 * @return User|null The authenticated user or null
 * @throws AuthenticationException
 */
```

## Best Practices

### 1. SOLID Principles

- **Single Responsibility**: Each class should have one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Subtypes must be substitutable for their base types
- **Interface Segregation**: Many specific interfaces are better than one general
- **Dependency Inversion**: Depend on abstractions, not concretions

### 2. Clean Code

- Write small, focused methods
- Use descriptive naming
- Implement early returns
- Avoid deep nesting
- Keep methods under 20 lines where possible

### 3. Error Handling

- Use exceptions for errors
- Catch specific exceptions
- Log meaningful error messages
- Use `TN\TN_Core\Error\CodeException` for catchable runtime errors

### 4. Comments and Naming

- Avoid comments where possible
- Make code self-documenting through clear naming
- Use intention-revealing names
- Choose clarity over brevity

## Version Control

### Commit Messages

- Keep messages simple and clear
- Summarize changes in 2 sentences
- List specific changes in bullet points
- Avoid technical jargon
- Example:
  ```
  Add user authentication and profile management
  
  - Implement JWT authentication
  - Add user profile CRUD operations
  - Create password reset flow
  - Add email verification
  ```

## Package and Module Organization

### Package Structure

- Package names should be 2-5 letter/number abbreviations
- Base package is `TN`
- Additional packages (e.g., `FBG` for Footballguys) extend base
- Packages must be manually created

### Module Structure

- Format: `{PACKAGE_NAME}_{MODULE_NAME}`
- Each module requires a `Module.php` file
- Example module structure:
  ```php
  namespace TN\TN_Billing;
  
  class Module extends \TN\TN_Core\Model\Package\Module
  {
      public string $package = \TN\Package::class;
      public string $name = 'TN_Billing';
  }
  ```

### Package Stack

- Package order defined in `.env` file
- Higher packages can override lower package files
- Edit files in the highest overriding package
- Base package (`TN`) typically lowest in stack 