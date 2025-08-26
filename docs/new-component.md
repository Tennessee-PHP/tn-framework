# Creating New Components

Quick reference for creating new TN Framework components.

## 1. Choose Component Type

- **HTMLComponent**: Web pages with templates and optional TypeScript
- **JSON**: API endpoints that return JSON responses  
- **CLI**: Command-line scripts and scheduled tasks

## 2. Component Organization and Naming

### Naming Conventions
- **Components**: Use verb-noun format (`ListUsers`, `HandleLogin`, `CreateEvent`)
- **APIs**: Use action-based names (`CreateMessage`, `UploadFile`, `DeleteUser`)
- **Don't use the word "View"** - e.g. Don't call a component "ViewProjections", just call it "Projections"
- Keep component names focused on their primary responsibility

### Directory Organization
Organize components into logical groups:

```
src/Package/Module/Component/
├── Admin/              # Admin-only components
│   ├── Users/
│   │   ├── ListUsers/
│   │   └── EditUser/
│   └── Settings/
├── User/               # User-facing components  
│   ├── Profile/
│   └── Dashboard/
└── Shared/             # Shared components
    ├── Navigation/
    └── Footer/
```

### File Structure
```
src/Package/Module/Component/Controller/ComponentName/
├── ComponentName.php      # Required: Main component class
├── ComponentName.tpl      # Required for HTMLComponent: Template  
├── ComponentName.ts       # Optional: TypeScript functionality
└── _ComponentName.scss    # Optional: Component styles
```

## 3. Create Component Class

### HTMLComponent
```php
<?php
namespace Package\Module\Component\Controller\ComponentName;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\FromPath;

class ComponentName extends HTMLComponent
{
    #[FromPath] public string $userId;
    #[FromQuery] public int $page = 1;
    
    public array $items = [];
    public string $pageTitle = '';
    
    public function prepare(): void
    {
        $this->items = Model::searchByProperties(['active' => true]);
        $this->pageTitle = 'Page Title';
    }
}
```

*See [PHP Components](php-components.md) for detailed patterns*

### JSON Component
```php
<?php
namespace Package\Module\API\Controller\ComponentName;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Error\ValidationException;

class ComponentName extends JSON
{
    #[FromPost] public string $name;
    #[FromPost] public string $email;
    
    public function prepare(): void
    {
        if (empty($this->name)) {
            throw new ValidationException('Name is required');
        }
        
        $record = Model::getInstance();
        $record->update(['name' => $this->name, 'email' => $this->email]);
        
        $this->data = [
            'result' => 'success',
            'message' => 'Created successfully'
        ];
    }
}
```

*See [PHP Components](php-components.md) for detailed API patterns*

## 4. Create Template

```smarty
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    <h1>{$pageTitle|escape}</h1>
    
    {if $items}
        {foreach $items as $item}
            <div>{$item.name|escape}</div>
        {/foreach}
    {else}
        <p>No items found.</p>
    {/if}
</div>
```

*See [Smarty Components](smarty-components.md) for framework integration details*

## 5. Add TypeScript (Optional)

```typescript
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ComponentName extends HTMLComponent {
    protected observe(): void {
        this.controls = [
            this.$element.find('#search-input'),
            this.$element.find('#filter-select')
        ];
        this.observeControls();
    }
}
```

*See [TypeScript Components](typescript-components.md) for detailed component patterns*

## 6. Add Controller Route

```php
<?php
namespace Package\Module\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\UsersOnly;
use TN\TN_Core\Attribute\Component;

class ControllerName extends Controller
{
    #[Path('admin/items')]
    #[UsersOnly]
    #[Component(\Package\Module\Component\ControllerName\ComponentName::class)]
    public function componentName(): void {}
}
```

*See [PHP Components](php-components.md) for routing details*

## 7. Update Component Map

After adding TypeScript files:

```bash
docker exec container-name php src/run.php components/map
npm run build
```

That's it! For detailed patterns, see:
- **[PHP Components](php-components.md)**
- **[Smarty Components](smarty-components.md)**  
- **[TypeScript Components](typescript-components.md)**
