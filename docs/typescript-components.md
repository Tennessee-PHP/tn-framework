# TypeScript Components

TypeScript components in the TN Framework extend HTMLComponent and follow specific patterns for controls, reloading, form handling, and API calls.

## Basic Component Structure

```typescript
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ListUsers extends HTMLComponent {
    protected updateUrlQueryOnReload: boolean = true;

    protected observe(): void {
        // Set this.controls with cash-dom instances, then call observeControls()
        this.controls = [
            this.$element.find('.op-tn_core-component-pagination-pagination'),
            this.$element.find('#username_search_field'),
            this.$element.find('#email_search_field'), 
            this.$element.find('#id_search_field')
        ];
        
        // Set up control observation - framework will handle automatic reload
        this.observeControls();
        
        // Set up event listeners - NEVER use anonymous functions, always bind class methods
        this.$element.on('click', '.delete-user-btn', this.onDeleteUserClick.bind(this));
        this.$element.on('change', '.user-status-checkbox', this.onUserStatusChange.bind(this));
    }
    
    /**
     * Handle delete user button clicks
     */
    private onDeleteUserClick(e: Event): void {
        const userId = $(e.currentTarget).data('user-id');
        this.deleteUser(userId);
    }
    
    /**
     * Handle user status checkbox changes
     */
    private onUserStatusChange(e: Event): void {
        const $checkbox = $(e.currentTarget as HTMLElement);
        const userId = parseInt($checkbox.data('user-id'));
        const isActive = ($checkbox[0] as HTMLInputElement).checked;
        this.updateUserStatus(userId, isActive);
    }
}
```

## Key Patterns

### 1. Event Listeners

**CRITICAL**: All event listeners must be set up in `observe()` and must NEVER use anonymous functions. Always bind to class methods:

```typescript
// CORRECT - Set up in observe(), bind to class method
protected observe(): void {
    this.$element.on('click', '.my-button', this.onButtonClick.bind(this));
    this.$element.on('change', '.my-checkbox', this.onCheckboxChange.bind(this));
}

private onButtonClick(e: Event): void {
    // Handle button click
}

private onCheckboxChange(e: Event): void {
    // Handle checkbox change
}

// WRONG - Anonymous function
this.$element.on('click', '.my-button', (e) => {
    // This violates the architecture
});

// WRONG - Set up outside observe()
private setupEventListeners(): void {
    this.$element.on('click', '.my-button', this.onButtonClick.bind(this));
}
```

### 2. Controls and Reload Data

- **Controls**: Initialize `this.controls` as an array of cash-dom elements in `observe()`
- **observeControls()**: Call this to set up automatic reloading when controls change
- **updateUrlQueryOnReload**: Set to `true` to update URL parameters on reload

#### Control Change Handlers

For controls that need to update their `data-value` attribute, set up change handlers BEFORE calling `observeControls()`:

```typescript
protected observe(): void {
    // Set up controls array
    this.controls = [];
    
    const $yearDropdown = this.$element.find('[data-year-dropdown]');
    if ($yearDropdown.length > 0) {
        this.controls.push($yearDropdown);
    }
    
    // Set up change handler BEFORE observeControls
    $yearDropdown.on('change', this.onYearChange.bind(this));
    
    // Set up control observation - framework handles reloads
    this.observeControls();
}

/**
 * Handle control change - update data-value before framework listener
 */
private onYearChange(e: Event): void {
    const $select = $(e.currentTarget as HTMLElement);
    const newYear = $select.val() as string;
    
    // Update the data-value attribute (simple value, not JSON)
    $select.attr('data-value', newYear);
}
```

**CRITICAL**: Control change handlers must update the `data-value` attribute with the new value (as a simple string, not JSON) so the framework's controls system can properly include the parameter in reload requests.

### 2. Property Initialization

Set component properties as class properties, not in the constructor:

```typescript
export default class MyComponent extends HTMLComponent {
    protected reloadMethod = 'POST';  // Set as class property
    protected updateUrlQueryOnReload: boolean = true;
}
```

**CRITICAL**: Do not use property initializers (default values) for properties that will be set in `observe()` or the constructor. Property initializers execute AFTER the constructor completes, overwriting values set during initialization:

```typescript
// WRONG - initializer will overwrite value set in observe()
private lastState: string = '';

// CORRECT - no initializer, set in observe() or constructor
private lastState: string;
```

### 3. Form Handling and File Uploads

For file uploads and form submissions:

```typescript
private uploadImage(file: File): void {
    const formData = new FormData();
    formData.append('image', file);

    // Show loading state
    const $uploadBtn = this.$element.find('#upload-image-btn');
    $uploadBtn.text('Uploading...').prop('disabled', true);

    // Get API endpoint from data attribute
    const apiEndpoint = this.$element.data('upload-url');
    
    // @ts-ignore
    axios.post(apiEndpoint, formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
    .then((response) => {
        // Show success state briefly, then reload
        Toast.success('Image uploaded successfully');
        $uploadBtn.text('Upload Image').prop('disabled', false);
        setTimeout(() => {
            this.reload();
        }, 1000);
    })
    .catch((error: any) => {
        Toast.error('Upload failed: ' + (error.response?.data?.message || error.message));
        $uploadBtn.text('Upload Image').prop('disabled', false);
    });
}
```

### 4. AJAX/API Calls

Use axios for AJAX calls with .then() and .catch():

```typescript
import axios from 'axios';

private makeApiCall(): void {
    // Get API endpoint from data attribute
    const apiEndpoint = this.$element.data('api-url');
    
    // @ts-ignore
    axios.post(apiEndpoint, data)
    .then((response) => {
        // Handle success
        if (response.data.success) {
            Toast.success(response.data.message);
            this.reload();
        }
    })
    .catch((error: any) => {
        // Handle error
        Toast.error(error.response?.data?.message || error.message);
    });
}
```

## Load More (Infinite Scroll) Components

### Overview

TypeScript components that use the `#[LoadMore]` attribute automatically inherit infinite scroll functionality from the base `HTMLComponent` class. No additional TypeScript code is required for basic infinite scroll.

### Automatic Behavior

The framework automatically:
- Detects scroll position and triggers load more requests
- Manages loading states and error handling  
- Updates the status container (loading/no-more messages)
- Appends new items to the existing container
- Observes new items for component initialization

### Custom Item Observation

Override `observeItems()` to add custom behavior for newly loaded items:

```typescript
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ListItems extends HTMLComponent {
    protected updateUrlQueryOnReload: boolean = true;

    protected observe(): void {
        // Initialize existing items on page load
        this.observeExistingItems();
    }

    /**
     * Observe existing items on page load
     */
    private observeExistingItems(): void {
        const $existingItems = this.$element.find('[data-items-container] > *');
        this.observeItems($existingItems);
    }

    /**
     * Override to add custom behavior for new items
     */
    protected observeItems($items: Cash): void {
        // Call parent to handle component instantiation
        super.observeItems($items);
        
        // Add custom item-specific functionality
        this.setupImageLazyLoading($items);
        this.setupItemHoverEffects($items);
        this.setupItemClickHandlers($items);
    }

    private setupImageLazyLoading($items: Cash): void {
        $items.find('img').each((i, img) => {
            const $img = $(img);
            if (!$img.attr('loading')) {
                $img.attr('loading', 'lazy');
            }
        });
    }

    private setupItemHoverEffects($items: Cash): void {
        $items.on('mouseenter', (e) => {
            $(e.currentTarget).addClass('hover-effect');
        }).on('mouseleave', (e) => {
            $(e.currentTarget).removeClass('hover-effect');
        });
    }

    private setupItemClickHandlers($items: Cash): void {
        $items.find('.item-action-btn').on('click', (e) => {
            e.preventDefault();
            const itemId = $(e.currentTarget).closest('[data-item-id]').data('item-id');
            this.handleItemAction(itemId);
        });
    }

    private handleItemAction(itemId: number): void {
        // Custom item action logic
    }
}
```

### Framework Integration

LoadMore components automatically integrate with:

- **Scroll Detection**: 500px from bottom triggers load more
- **Throttling**: 1-second delay prevents rapid requests  
- **Error Handling**: Stops requests on error, shows error toast
- **State Management**: Manages `loadingMore` and `hasMore` states
- **DOM Updates**: Appends new items and updates status messages

### Status Container Management

The framework automatically manages status messages using template data attributes:

- **`[data-load-more-state="loading"]`** - Shows during load requests
- **`[data-load-more-state="no-more"]`** - Shows when no more items exist

No TypeScript code needed - the framework handles show/hide automatically.

### Best Practices

- **Keep TypeScript behavioral only** - Never put HTML strings in TypeScript
- **Use data attributes** - Let templates control content and structure  
- **Override observeItems()** - Add custom behavior for newly loaded items
- **Call super.observeItems()** - Ensure framework component initialization
- **Test with existing items** - Initialize behavior for items on page load

That's it. These are the core patterns for TN Framework TypeScript components.