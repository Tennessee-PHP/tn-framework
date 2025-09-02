# PHP Coding Standards

## Overview

These standards ensure consistent, maintainable, and high-quality PHP code across all TN Framework projects. Following these standards is mandatory for all contributions.

## 🚨 CRITICAL FRAMEWORK RULES 🚨

### ❌ NEVER VIOLATE THESE RULES ❌

1. **NEVER use string literals for class references - ALWAYS use `::class`**
   ```php
   // ❌ WRONG - String literals are forbidden
   $className = 'MyNamespace\\MyClass';
   Stack::resolveClassName('Package\\Model\\User');
   
   // ✅ CORRECT - Always use ::class
   $className = MyClass::class;
   Stack::resolveClassName(User::class);
   ```

2. **Framework code MUST NEVER reference site-specific code**
   ```php
   // ❌ WRONG - Framework referencing site code
   use NE\NE_Main\Model\Something;
   Stack::resolveClassName('NE_Main\\Model\\Comment');
   
   // ✅ CORRECT - Framework stays generic
   use TN\TN_Core\Model\Something;
   Stack::resolveClassName(Comment::class);
   ```

**Violating these rules breaks the entire framework architecture and is unforgivable.**

3. **ALWAYS wrap column names in backticks for SearchComparison**
   ```php
   // ❌ WRONG - Column names without backticks are treated as string literals
   new SearchComparison('screenshotId', 'IN', [1, 2, 3]);    // Generates: ? IN (1,2,3) [BROKEN]
   new SearchComparison('username', '=', 'john');            // Generates: 'username' = 'john' [WRONG]
   
   // ✅ CORRECT - Always use backticks for column names
   new SearchComparison('`screenshotId`', 'IN', [1, 2, 3]);  // Generates: screenshotId IN (1,2,3)
   new SearchComparison('`username`', '=', 'john');          // Generates: username = 'john'
   ```

## Execution Environment

- All PHP scripts, including tests, MUST be executed from within the appropriate Docker container
- The working directory inside the container should be `/var/www/html`
- Any command-line PHP execution (e.g., `php`, `phpunit`, schema generation, etc.) must be run using `docker exec` commands
- Do NOT run PHP scripts directly on the host machine

## File Structure

```
src/
├── Package/
│   └── Package_Module/
│       ├── Component/          # HTML components and API endpoints
│       ├── Controller/         # Route controllers
│       ├── Model/              # Data models and business logic
│       ├── CLI/                # Command-line interfaces
│       └── Module.php          # Module definition
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
  <?php
  declare(strict_types=1);
  ```
- Use type hints for parameters and return types
- Use nullable types with `?` prefix
- Return type declarations are mandatory

```php
public function findUser(?string $id): ?User
{
    return $id ? $this->repository->find($id) : null;
}
```

## Code Style

- 4 spaces for indentation
- 120 character line limit
- Use short array syntax `[]`
- Single quotes for strings unless interpolating
- PSR-12 compliant
- Never use fully qualified class names in code (except in attributes - see Import Patterns)
- Always use `use` statements at the top of files for imports
- For attributes and docblocks preceding class properties, place them on the same line as the property declaration

## Import Patterns

### Simple Rules

1. **USE the `use` statement** - Import classes at the top of your file
2. **DON'T use `use` for extends** - Always use full namespace in extends clause
3. **DON'T use `use` for controller component/path attributes** - Always use full namespace in #[Component], #[DynamicPath], etc.

### Examples

```php
// ✅ CORRECT - Use imports for regular class usage
use TN\TN_Core\Model\User\Role;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;

// ✅ CORRECT - Full namespace for extends (no import)
class User extends \TN\TN_Core\Model\User\User
{
    public function addRole(Role $role): void { }
    
    public function validate(): void 
    { 
        throw new ValidationException('Invalid');
        $timestamp = Time::getNow();
    }
}

// ✅ CORRECT - Full namespace for controller attributes (no import)
#[Component(\Package\Module\Component\ControllerName\ComponentName::class)]
#[DynamicPath(\Package\Module\Component\ControllerName\ComponentName::class, 'dynamicMatch')]
public function myAction(): void {}
```

```php
// ❌ INCORRECT - Don't import parent classes
use TN\TN_Core\Model\User\User as BaseUser;
class User extends BaseUser { }

// ❌ INCORRECT - Don't import for controller attributes
use Package\Module\Component\ControllerName\ComponentName;
#[Component(ComponentName::class)]
```

## Error Handling

### Exception Usage
- Use `TN\TN_Core\Error\ValidationException` for user-facing validation errors
- Use `TN\TN_Core\Error\CodeException` only for internal application errors
- NEVER use PHP's built-in exceptions (RuntimeException, Exception, etc.) directly
- Let framework components handle their own error responses - don't duplicate error handling
- Don't check boolean returns of methods that throw exceptions (e.g. save())
- Keep error messages user-friendly and avoid exposing internal details
- When access could be denied, use combined messages like "not found or access denied"

### Error Response Pattern
```php
// DO THIS:
if (empty($records)) {
    throw new ValidationException('Record not found or access denied');
}

// NOT THIS:
if (!$record) {
    throw new CodeException('Record not found');
} elseif (!$record->belongsToUser($user)) {
    throw new CodeException('Access denied');
}
```

## Database Queries

### Efficient Querying
- Use searchByProperties for checking multiple conditions in one query
- Avoid multiple queries when one will do
- When checking both existence and ownership, combine into one query
- For complex queries with conditions, sorting, and limits, use SearchArguments

```php
// Simple property search
$records = Model::searchByProperties([
    'id' => $id,
    'userId' => $user->id
]);
if (empty($records)) {
    throw new ValidationException('Record not found or access denied');
}

// Complex search with SearchArguments
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchCondition;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;

$searchArgs = new SearchArguments(
    conditions: [
        new SearchCondition('status', 'active'),
        new SearchCondition('userId', $user->id)
    ],
    sorters: [
        new SearchSorter('createdAt', 'DESC'),
        new SearchSorter('name', 'ASC')
    ],
    limit: new SearchLimit(20, 0) // limit 20, offset 0
);

$records = Model::search($searchArgs);

// DON'T DO THIS - Multiple separate queries:
$record = Model::readFromId($id);
if (!$record) {
    throw new ValidationException('Record not found');
}
if (!$record->belongsToUser($user)) {
    throw new ValidationException('Access denied');
}
```

## Model Patterns

### Model Creation & Updates
```php
// ✅ GOOD: Create new model instance (no arguments to getInstance)
$user = User::getInstance();

// ✅ GOOD: Set properties using update() method (persists automatically)
$user->update([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'isActive' => true
]);

// ✅ GOOD: Update existing model (persists automatically)
$existingUser = User::readFromId($userId);
$existingUser->update([
    'email' => 'newemail@example.com',
    'isActive' => false
]);

// ❌ BAD: Never pass data to getInstance() (unless called by MySQL framework)
$user = User::getInstance([
    'username' => 'johndoe',  // This causes framework conversion errors
    'email' => 'john@example.com'
]);

// ❌ BAD: Never set persistent properties directly
$user = User::getInstance();
$user->username = 'johndoe';  // Direct assignment bypasses framework
$user->email = 'john@example.com';

// ❌ BAD: Never call save() - only framework code should use this
$user->update(['name' => 'John']);
$user->save();  // NEVER call save() - update() persists automatically
```

### Factory Methods Exception
When factory methods need discriminator data to return the correct subclass (e.g., `Slate::getInstance(['sport' => 'american_football'])` to get `AmericanFootballSlate`), pass minimal discriminator data only. This exception is reluctantly allowed for proper subclass instantiation when needed.

## Documentation

### PHPDoc Requirements
```php
/**
 * Generate a unique username from an email address
 * 
 * Takes the local part of an email (before @) and ensures uniqueness
 * by appending numbers if the username already exists.
 * 
 * @param string $email Valid email address to derive username from
 * @return string Unique username (3-25 characters, alphanumeric + underscore)
 * 
 * @throws ValidationException If email format is invalid
 * 
 * @example generateUsernameFromEmail('john.doe@example.com') // Returns 'johndoe' or 'johndoe1'
 */
protected function generateUsernameFromEmail(string $email): string
```

### Requirements
- **ALL public methods MUST have complete PHPDoc**
- **INCLUDE @param** for all parameters with types and descriptions
- **INCLUDE @return** with type and description
- **ADD @throws** for any exceptions
- **USE @example** for complex methods
- Attach documentation to PHP classes, not files, always
- This means a single docblock above the class declaration
- Don't add @property tags in docblocks

## Best Practices

### SOLID Principles
- **Single Responsibility**: Each class should have one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Subtypes must be substitutable for their base types
- **Interface Segregation**: Many specific interfaces are better than one general
- **Dependency Inversion**: Depend on abstractions, not concretions

### Clean Code
- Write small, focused methods
- Use descriptive naming
- Implement early returns
- Avoid deep nesting
- Keep methods under 20 lines where possible
- Let framework handle common patterns
- Don't duplicate existing functionality

### Comments and Naming
- Avoid comments wherever possible
- Make code self-documenting through clear naming
- Use intention-revealing names
- Choose clarity over brevity

## Property Visibility for Testability

Do not use private properties for dependencies or collaborators that may need to be stubbed or mocked in tests. Use protected visibility unless there is a strong reason for encapsulation. This ensures that tests and subclasses can override or inject dependencies as needed, reducing friction and refactor churn.

## Namespace and Directory Structure

Always keep PHP namespaces in sync with the directory structure. If a file is moved, its namespace must be updated to match its new location, and all references must be updated accordingly.

## Debugging Rules

- For all debugging output in PHP, always use var_dump
- Do not use error_log, file_put_contents, or print_r for debugging output
- Never use var_dump in template files for debugging

## Version Control

### Commit Messages
- Use Conventional Commit messages
- Keep messages simple and clear
- Summarize changes in 2 sentences
- List specific changes in bullet points
- Avoid technical jargon
- Example:
  ```
  feat: add user authentication and profile management
  
  - Implement JWT authentication
  - Add user profile CRUD operations
  - Create password reset flow
  - Add email verification
  ```
