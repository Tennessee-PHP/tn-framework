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
    }
}
```

## Key Patterns

### 1. Controls and Reload Data

- **Controls**: Initialize `this.controls` as an array of cash-dom elements in `observe()`
- **observeControls()**: Call this to set up automatic reloading when controls change
- **updateUrlQueryOnReload**: Set to `true` to update URL parameters on reload

### 2. Property Initialization

Set component properties as class properties, not in the constructor:

```typescript
export default class MyComponent extends HTMLComponent {
    protected reloadMethod = 'POST';  // Set as class property
    protected updateUrlQueryOnReload: boolean = true;
}
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

That's it. These are the core patterns for TN Framework TypeScript components.