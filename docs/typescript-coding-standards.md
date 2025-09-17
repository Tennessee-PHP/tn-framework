# TypeScript Coding Standards

## Overview

TypeScript in the TN Framework provides client-side functionality for HTML components, handles user interactions, and manages component state. These standards ensure consistent, maintainable TypeScript code across all projects.

## Configuration and Approach

- Never change the tsconfig.json file without explicit permission
- Don't add any interfaces for TinyMCE - just use `@ts-ignore` to suppress related errors
- Don't be afraid to use `// @ts-ignore` for framework integration issues
- Wherever the global TN variable is used, use `@ts-ignore` to suppress errors
- Same with any global project variables used

## Dependencies

### cash-dom

- Use cash-dom for ALL DOM querying and manipulation
- Never use jQuery
- Never use vanilla JavaScript DOM methods
- All `$` references are to cash-dom, not jQuery

```typescript
// ✅ GOOD - cash-dom usage
const element = $('.my-element');
element.addClass('active');
const button = $('#submit-button');
button.on('click', handleClick);

// ❌ BAD - jQuery patterns
$('.my-element').fadeIn(); // cash-dom doesn't have fadeIn

// ❌ BAD - Vanilla JavaScript DOM methods
const element = document.querySelector('.my-element'); // Never use vanilla JS
const button = document.getElementById('submit-button'); // Never use vanilla JS
element.classList.add('active'); // Never use vanilla JS
button.addEventListener('click', handleClick); // Never use vanilla JS
```

### Data Attributes

**Always use `.data()` methods for data attributes, never `.attr('data-...')`:**

```typescript
// ✅ GOOD - Use .data() methods for reading/setting, removeAttr for removing
const apiUrl = this.$element.data('api-url');
const userId = this.$element.data('user-id');
button.data('loading', 'true');
button.data('user-reacted', 'false');
button.removeAttr('data-loading'); // Remove data attribute

// ❌ BAD - Never use .attr() for data attributes
const apiUrl = this.$element.attr('data-api-url'); // Never do this
const userId = this.$element.attr('data-user-id'); // Never do this
button.attr('data-loading', 'true'); // Never do this
```

## Type Safety

### Framework Integration

- Use `@ts-ignore` for framework integration
- Don't fight TypeScript when working with TN Framework globals

```typescript
// @ts-ignore
const tn = window.TN;

// @ts-ignore  
const projectGlobal = window.PROJECT_VAR;
```

### Type Declarations

- Use type hints for parameters and return types where possible
- Use nullable types with `?` or union types with `| null`

```typescript
interface UserData {
    id: number;
    name: string;
    email?: string;
}

function processUser(user: UserData): boolean {
    return user.id > 0;
}

function findUser(id: number): UserData | null {
    // Implementation
    return null;
}
```

## Event Handling

### Framework Event Binding

**Always use proper `.bind(this)` for event handlers** - Never use nested anonymous functions or lose context:

```typescript
// ✅ GOOD - Clean framework pattern with proper binding
protected observe(): void {
    this.initializeData();
    this.$element.find('button').on('click', this.handleButtonClick.bind(this));
    this.$element.find('form').on('submit', this.handleFormSubmit.bind(this));
}

private handleButtonClick(e: Event): void {
    e.preventDefault();
    const $button = $(e.target as HTMLElement);
    // Handle button click with proper context
}

// ❌ BAD - Disgusting nested anonymous methods that lose context
this.$element.find('button').each((index, element) => {
    const $button = $(element);
    $button.on('click', (e) => {
        e.preventDefault();
        this.handleButtonClick($button); // Passing element instead of event
    });
});

// ❌ BAD - Arrow functions that can lose context
this.$element.find('button').on('click', (e) => this.handleClick(e));
```

### Event Handler Signatures

**Always use standard Event parameter** - Don't create custom parameter signatures:

```typescript
// ✅ GOOD - Standard event signature
private handleClick(e: Event): void {
    e.preventDefault();
    const $target = $(e.target as HTMLElement);
    // Work with the target element
}

// ❌ BAD - Custom element parameter
private handleClick(button: HTMLElement): void {
    // This breaks the framework pattern
}

// ❌ BAD - Cash parameter
private handleClick($button: Cash): void {
    // This breaks the framework pattern
}
```

### Context and Binding Best Practices

**Never lose `this` context through lazy coding** - Always be explicit about binding:

```typescript
// ✅ GOOD - Explicit binding maintains context
protected observe(): void {
    this.$element.find('.toggle').on('click', this.handleToggle.bind(this));
    this.$element.find('.save').on('click', this.handleSave.bind(this));
}

// ✅ GOOD - Proper method binding for callbacks
private setupTimer(): void {
    setInterval(this.updateStatus.bind(this), 1000);
}

// ❌ BAD - Losing context through lazy habits
protected observe(): void {
    const self = this; // Never use self = this
    this.$element.find('.toggle').on('click', function() {
        self.handleToggle(); // Ugly context workaround
    });
}

// ❌ BAD - Double-nested anonymous functions
this.$element.find('button').each((i, el) => {
    $(el).on('click', (e) => {
        this.doSomething($(el)); // Messy nested closures
    });
});
```

## Code Style

### HTML in TypeScript

**Never hardcode HTML in TypeScript** - Always put HTML structure in Smarty templates:

```typescript
// ❌ BAD - Never hardcode HTML in TypeScript
const html = `<div class="alert alert-success">${message}</div>`;
this.$element.append(html);

// ✅ GOOD - Use templates and data attributes for dynamic content
this.$element.find('.message-container').text(message);
this.$element.find('.alert').removeClass('d-none');
```

### API Endpoints

**Never hardcode API paths** - Use data attributes from templates:

```typescript
// ❌ BAD - Hardcoded API path
axios.post('/api/admin/users/create', data).then((response) => {
    // Handle response
});

// ✅ GOOD - Read from data attribute
const apiUrl = this.$element.data('api-url');
axios.post(apiUrl, data).then((response) => {
    // Handle response
});
```

### General Style

- 4 spaces for indentation
- 120 character line limit
- Use single quotes for strings unless interpolating
- Use semicolons consistently
- Prefer const over let, let over var

```typescript
const userName = 'john';
let currentPage = 1;

const config = {
    timeout: 5000,
    retries: 3
};
```

## Import Patterns

### TN Framework Components

- Always use the TypeScript alias for HTMLComponent imports
- Use the standard import path format

```typescript
// Good - Using TypeScript alias
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';

// Bad - Relative paths
import HTMLComponent from '../../../../TN_Core/Component/HTMLComponent';
```

### Module Imports

```typescript
// Standard library imports
import axios, { AxiosResponse, AxiosError } from 'axios';
import _ from 'lodash';

// Framework imports
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import { SuccessToast, ErrorToast } from '@tn/TN_Core/Component/Toast';

// Local imports
import { ComponentConfig } from './types';
```



## HTTP Requests

### Axios Integration

**Use Axios for all HTTP requests** - Never use `fetch()` or other HTTP libraries:

```typescript
import axios, { AxiosResponse, AxiosError } from 'axios';

// ✅ GOOD - Axios with proper promise chaining (not async/await)
private makeRequest(endpoint: string, data: any): void {
    axios.post(endpoint, new URLSearchParams(data))
        .then((response: AxiosResponse) => {
            this.handleSuccess(response.data);
        })
        .catch((error: AxiosError) => {
            this.handleError(error);
        })
        .finally(() => {
            this.clearLoadingState();
        });
}

// ❌ BAD - Using fetch() instead of axios
fetch(endpoint, {
    method: 'POST',
    body: JSON.stringify(data)
}).then(response => response.json()); // Never use fetch

// ❌ BAD - Using async/await (prefer promise chains)
private async makeRequest(endpoint: string): Promise<any> {
    try {
        const response = await axios.get(endpoint);
        return response.data;
    } catch (error) {
        throw error;
    }
}
```

### Form Data Handling

**Always use `URLSearchParams` for form-encoded data** - Never send JSON to form endpoints:

```typescript
// ✅ GOOD - Form-encoded data for PHP endpoints
axios.post(apiUrl, new URLSearchParams({
    modelType: this.contentType,
    contentId: this.contentId.toString(),
    reactionType: reactionType
}));

// ❌ BAD - Sending JSON to form endpoints
axios.post(apiUrl, {
    modelType: this.contentType,
    contentId: this.contentId,
    reactionType: reactionType
}); // This won't work with #[FromPost] attributes
```

### Promise Patterns

**Use `.then().catch().finally()` chains** - Avoid async/await for simple HTTP requests:

```typescript
// ✅ GOOD - Clean promise chain
private submitData(): void {
    button.data('loading', 'true');
    
    axios.post(this.apiUrl, this.prepareData())
        .then((response) => {
            this.updateUI(response.data);
            new SuccessToast('Data saved successfully');
        })
        .catch((error) => {
            console.error('Request failed:', error);
            new ErrorToast('Failed to save data');
        })
        .finally(() => {
            button.removeAttr('data-loading');
        });
}

// ❌ BAD - Unnecessary async/await complexity
private async submitData(): Promise<void> {
    try {
        button.data('loading', 'true');
        const response = await axios.post(this.apiUrl, this.prepareData());
        this.updateUI(response.data);
        new SuccessToast('Data saved successfully');
    } catch (error) {
        console.error('Request failed:', error);
        new ErrorToast('Failed to save data');
    } finally {
        button.removeAttr('data-loading');
    }
}
```

## JSON Response Handling

### Standard Response Format

**ALWAYS check `response.data.result`** to determine success/error status, consistent with framework standards:

```typescript
// ✅ GOOD - Standard response handling
axios.post(apiUrl, formData)
    .then((response) => {
        if (response.data.result === 'success') {
            new SuccessToast(response.data.message || 'Operation completed successfully');
            this.handleSuccess(response.data);
        } else {
            throw new Error(response.data.message || 'Operation failed');
        }
    })
    .catch((error) => {
        console.error('Request failed:', error);
        const errorMessage = error.response?.data?.message || error.message || 'Operation failed. Please try again.';
        new ErrorToast(errorMessage);
    });

// ❌ BAD - Inconsistent response checking
axios.post(apiUrl, formData)
    .then((response) => {
        if (response.data.success) {           // Don't check 'success' field
            // Handle success
        }
        if (response.data.status === 'ok') {   // Don't check 'status' field
            // Handle success  
        }
    });
```

### Error Response Handling

**Handle both HTTP errors and application errors** using the standard `result` field:

```typescript
// ✅ GOOD - Comprehensive error handling
private makeApiCall(): void {
    axios.post(apiUrl, data)
        .then((response) => {
            // Check application-level success/error
            if (response.data.result === 'success') {
                new SuccessToast(response.data.message);
                this.processSuccessResponse(response.data);
            } else if (response.data.result === 'error') {
                // Application returned error response
                throw new Error(response.data.message || 'Application error occurred');
            } else {
                // Unexpected response format
                throw new Error('Unexpected response format');
            }
        })
        .catch((error) => {
            console.error('API call failed:', error);
            
            // Extract error message from various sources
            let errorMessage = 'Request failed. Please try again.';
            
            if (error.response?.data?.message) {
                // Server returned error with message
                errorMessage = error.response.data.message;
            } else if (error.message) {
                // JavaScript error or thrown error
                errorMessage = error.message;
            }
            
            new ErrorToast(errorMessage);
        });
}

// ❌ BAD - Incomplete error handling
private makeApiCall(): void {
    axios.post(apiUrl, data)
        .then((response) => {
            // Assumes all 200 responses are successful
            new SuccessToast('Success!');
        })
        .catch((error) => {
            // Generic error handling
            new ErrorToast('Error occurred');
        });
}
```

### Response Type Patterns

**Use consistent patterns for different response scenarios**:

```typescript
// ✅ GOOD - Consistent response handling patterns
private handleApiResponse(response: any): void {
    switch (response.data.result) {
        case 'success':
            new SuccessToast(response.data.message || 'Operation completed');
            if (response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                this.reload(); // Refresh component
            }
            break;
            
        case 'error':
            throw new Error(response.data.message || 'Operation failed');
            
        default:
            throw new Error('Unexpected response format');
    }
}

// ❌ BAD - Inconsistent response handling
private handleApiResponse(response: any): void {
    if (response.data.success === true) {        // Wrong field
        // Success handling
    } else if (response.data.error) {            // Wrong field
        // Error handling  
    } else if (response.status === 200) {        // Wrong level
        // Assumes HTTP 200 = success
    }
}
```

## Error Handling

### Try-Catch Patterns

```typescript
private async loadData(): Promise<void> {
    try {
        const response = await axios.get('/api/data');
        this.processData(response.data);
        
    } catch (error) {
        console.error('Failed to load data:', error);
        new ErrorToast('Failed to load data');
    }
}
```

### User Feedback Patterns

**Always use Toast components for user feedback** - Never use `console.log` or `alert()`:

```typescript
import SuccessToast from '@ne/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@ne/TN_Core/Component/Toast/ErrorToast';

// ✅ GOOD - Proper user feedback with Toast components
private handleSuccess(message: string): void {
    new SuccessToast(message);
}

private handleError(error: any): void {
    console.error('Operation failed:', error); // Log for debugging
    new ErrorToast('Operation failed. Please try again.');
}

// ❌ BAD - Using console.log for user feedback
private handleSuccess(): void {
    console.log('Success!'); // Users can't see this
}

// ❌ BAD - Using alert() for user feedback
private handleError(): void {
    alert('Error occurred!'); // Ugly and blocks UI
}
```

### Error Message Patterns

```typescript
// ✅ GOOD - Comprehensive error handling with user feedback
private processData(): void {
    axios.post(this.apiUrl, this.data)
        .then((response) => {
            new SuccessToast('Data processed successfully');
            this.updateUI(response.data);
        })
        .catch((error) => {
            console.error('Processing failed:', error); // For developers
            new ErrorToast('Failed to process data'); // For users
        });
}

// ❌ BAD - Console-only error handling
private processData(): void {
    try {
        // ... some processing
        console.log('Data processed successfully'); // Users can't see this
    } catch (error) {
        console.error('Processing failed:', error); // Only developers see this
    }
}
```

## Performance Optimization

### Debouncing and Throttling

```typescript
import _ from 'lodash';

// Debounce function calls to improve performance
const debouncedSearch = _.debounce((query: string) => {
    performSearch(query);
}, 300);

// Throttle frequent events
const throttledScroll = _.throttle(() => {
    handleScrollEvent();
}, 100);

// Usage
input.addEventListener('input', (event) => {
    debouncedSearch(event.target.value);
});

window.addEventListener('scroll', throttledScroll);
```

### Memory Management

```typescript
// Clean up timers and event listeners
class DataManager {
    private intervals: number[] = [];
    private timeouts: number[] = [];
    
    addPeriodicTask(callback: Function, interval: number): void {
        const id = setInterval(callback, interval);
        this.intervals.push(id);
    }
    
    cleanup(): void {
        // Clear all intervals
        this.intervals.forEach(id => clearInterval(id));
        this.intervals = [];
        
        // Clear all timeouts
        this.timeouts.forEach(id => clearTimeout(id));
        this.timeouts = [];
    }
}
```

## Best Practices

### Code Organization

- Keep functions small and focused
- Use descriptive function and variable names
- Group related functionality together
- Separate concerns (data processing, API calls, utility functions)

```typescript
// Good - Organized class structure
class UserService {
    // Properties
    private apiEndpoint: string;
    private cache: Map<number, UserData> = new Map();
    
    // Public methods
    public async getUser(id: number): Promise<UserData | null> { }
    public async updateUser(id: number, data: Partial<UserData>): Promise<void> { }
    
    // Private helpers
    private validateUserData(data: any): boolean { }
    private cacheUser(user: UserData): void { }
}

// Good - Utility functions
function formatCurrency(amount: number, currency: string = 'USD'): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

function debounce<T extends (...args: any[]) => any>(
    func: T,
    delay: number
): (...args: Parameters<T>) => void {
    let timeoutId: number;
    return (...args: Parameters<T>) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(null, args), delay);
    };
}
```

### Error Prevention

- Use optional chaining and nullish coalescing
- Validate data before processing
- Provide meaningful error messages

```typescript
// Use optional chaining
const username = user?.profile?.name ?? 'Unknown';

// Validate function parameters
function processUserData(userData: unknown): UserData | null {
    if (!userData || typeof userData !== 'object') {
        console.warn('Invalid user data provided');
        return null;
    }
    
    const data = userData as any;
    if (!data.id || !data.name) {
        console.error('Required user fields missing:', { id: data.id, name: data.name });
        return null;
    }
    
    return data as UserData;
}

// Safe array operations
const firstItem = items?.length > 0 ? items[0] : null;
const hasItems = Array.isArray(items) && items.length > 0;
```

### Build Process Integration

- Always run `npm run build` after making TypeScript changes
- TypeScript changes will not take effect until webpack rebuilds the bundle
- Update component maps when adding new TypeScript files

```bash
# Update component map
docker exec container-name php src/run.php components/map

# Build TypeScript
npm run build
```

This workflow is critical and must never be forgotten, as it's a common source of bugs when TypeScript changes don't seem to work.

