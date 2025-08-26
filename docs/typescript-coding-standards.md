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

- Use Axios for all HTTP requests
- Use proper error handling with try-catch
- Handle loading states appropriately

```typescript
import axios, { AxiosResponse, AxiosError } from 'axios';

private async loadData(endpoint: string): Promise<any> {
    try {
        const response: AxiosResponse = await axios.get(endpoint);
        return response.data;
        
    } catch (error: AxiosError) {
        console.error('API request failed:', error);
        throw error;
    }
}

private async saveData(endpoint: string, data: any): Promise<any> {
    try {
        const response: AxiosResponse = await axios.post(endpoint, data, {
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        return response.data;
        
    } catch (error: AxiosError) {
        console.error('Save failed:', error);
        throw error;
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

### Error Message Patterns

```typescript
// Basic error handling
try {
    await this.processData();
    console.log('Data processed successfully');
} catch (error) {
    console.error('Processing failed:', error);
    // Handle error appropriately
}

// Async/await error patterns
async function fetchUserData(id: number): Promise<UserData | null> {
    try {
        const response = await axios.get(`/api/users/${id}`);
        return response.data;
    } catch (error) {
        console.error('Failed to fetch user:', error);
        return null;
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

