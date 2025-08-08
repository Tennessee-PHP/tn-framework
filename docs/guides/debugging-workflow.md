# Debugging Workflow Guide

## Overview

This guide provides a systematic approach for AI agents to debug coding issues in the TN Framework codebase. The process covers diagnosis, confirmation, implementation, and validation to ensure robust fixes with minimal side effects.

## Types of Issues

### 1. PHP Error Reports
- Runtime errors, exceptions, or fatal errors
- Performance issues
- Logic errors producing incorrect results

### 2. User Bug Reports
- Ideally provided with "steps to replicate"
- Feature not working as expected
- UI/UX issues
- Data inconsistencies

## Step 1: Issue Diagnosis

### For PHP Errors

#### Finding Error Logs

**Location**: All PHP error logs are now accessible in the `./logs/` directory in your project root.

**Key Log Files:**
- `./logs/fpm-php.www.log` - PHP application errors and debug messages
- `./logs/nginx/access.log` - HTTP request logs
- `./logs/nginx/error.log` - Nginx server errors

**Viewing Logs:**
```bash
# View latest PHP errors
tail -f logs/fpm-php.www.log

# View last 50 PHP error lines
tail -50 logs/fpm-php.www.log

# Search for specific errors
grep -i "error\|fatal\|exception" logs/fpm-php.www.log

# Filter by timestamp (last hour)
grep "$(date '+%d-%b-%Y %H')" logs/fpm-php.www.log
```

**Adding Debug Statements:**
```php
// Use error_log for debugging
error_log("Debug: User ID = " . $user->id);
error_log("Debug: Query result = " . print_r($results, true));

// Use with context
error_log("UserProfile::prepare() - Loading user " . $userId);
```

#### Error Analysis Process

1. **Identify the Error Type:**
   - Fatal Error: Code syntax or missing files
   - Exception: Handled error with stack trace  
   - Warning/Notice: Non-fatal issues
   - Logic Error: Wrong output, no PHP error

2. **Locate the Source:**
   - Check stack trace for exact file and line
   - Identify the component: Model, Controller, Component
   - Determine if it's framework code or application code

3. **Understand the Context:**
   - What user action triggered the error?
   - What data was being processed?
   - Are there related errors before/after?

### For User Bug Reports

#### Information Gathering

**Required Information:**
- Exact steps to replicate the issue
- Expected vs actual behavior
- User account details (if relevant)
- Browser/device information (for UI issues)
- Timestamp of when issue occurred

**Example Good Report Format:**
```
Steps to Replicate:
1. Login as user 'john@example.com'
2. Navigate to /user/profile
3. Click "Edit Profile" button
4. Change first name to "Jonathan"
5. Click "Save"

Expected: Profile should update and show success message
Actual: Page shows "An error occurred" and profile is not updated
```

#### Reproducing the Issue

1. **Follow Exact Steps:**
   - Use the same user account if possible
   - Follow steps in exact order
   - Test in same environment (browser, etc.)

2. **Gather Debug Information:**
   ```php
   // Add debugging to suspected components
   error_log("UserProfile::handleFormSubmission() - POST data: " . print_r($_POST, true));
   error_log("UserProfile::handleFormSubmission() - User ID: " . User::getActive()->id);
   ```

3. **Check Related Systems:**
   - Database state before/after
   - Session data
   - Cache state
   - Related component functionality

## Step 2: Seek End-User Confirmation

Before implementing any fix, confirm your diagnosis with clear communication:

### Diagnosis Confirmation Template

```
## Issue Diagnosis

**Problem Identified:** [Brief description of the root cause]

**Technical Details:**
- Component affected: [Package\Module\Component\ClassName]
- Root cause: [Specific technical issue]
- Impact: [What functionality is broken]

**Proposed Solution:**
[High-level description of the fix approach]

**Files to be Modified:**
- `src/[path]/[file].php` - [Description of changes]
- `src/[path]/[template].tpl` - [Description of changes]

**Testing Plan:**
- [ ] Verify original issue is resolved
- [ ] Test related functionality
- [ ] Check for side effects

**Questions for Confirmation:**
1. Does this diagnosis match your understanding of the issue?
2. Are there any additional symptoms or related issues?
3. Should we proceed with the proposed solution?
```

## Step 3: Implement the Fix

### Code Modification Process

1. **Create Debug Version First:**
   ```php
   // Add extensive logging to understand data flow
   error_log("DEBUG: Starting fix validation in " . __METHOD__);
   error_log("DEBUG: Input data: " . print_r($inputData, true));
   ```

2. **Make Incremental Changes:**
   - Fix one logical issue at a time
   - Test each change individually
   - Keep original code commented for reference

3. **Follow Framework Patterns:**
   - Use proper validation with `ValidationException`
   - Follow TN Framework component patterns
   - Maintain consistent error handling

### Example Fix Implementation

```php
// Before: Problematic code
public function updateProfile(): void
{
    $user = User::getActive();
    $user->update($_POST); // No validation
}

// After: Fixed code with proper validation
public function updateProfile(): void
{
    error_log("UserProfile::updateProfile() - Starting update for user " . User::getActive()->id);
    
    $user = User::getActive();
    
    // Validate required fields
    if (empty($_POST['first'])) {
        error_log("UserProfile::updateProfile() - Validation failed: first name required");
        throw new ValidationException('First name is required');
    }
    
    if (empty($_POST['last'])) {
        error_log("UserProfile::updateProfile() - Validation failed: last name required");
        throw new ValidationException('Last name is required');
    }
    
    // Sanitize input data
    $updateData = [
        'first' => trim($_POST['first']),
        'last' => trim($_POST['last']),
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    error_log("UserProfile::updateProfile() - Update data: " . print_r($updateData, true));
    
    try {
        $user->update($updateData);
        error_log("UserProfile::updateProfile() - Successfully updated user " . $user->id);
        
    } catch (Exception $e) {
        error_log("UserProfile::updateProfile() - Update failed: " . $e->getMessage());
        throw new ValidationException('Failed to update profile. Please try again.');
    }
}
```

## Step 4: Side Effect Analysis

### Identify Potentially Affected Code

1. **Search for Dependencies:**
   ```bash
   # Find files that use the modified class/method
   grep -r "ClassName" src/
   grep -r "methodName" src/
   
   # Find components that include modified templates
   grep -r "TemplateName.tpl" src/
   ```

2. **Check Related Functionality:**
   - Components that extend the modified class
   - Templates that include the modified template
   - Models that have relationships to modified models
   - Controllers that use modified components

3. **Database Impact Analysis:**
   - If model properties changed: Check schema implications
   - If validation added: Ensure existing data is compatible
   - If relationships modified: Verify foreign key constraints

### Testing Checklist

**Core Functionality:**
- [ ] Original issue is resolved
- [ ] Modified component works as expected
- [ ] All validation rules function correctly
- [ ] Error handling works properly

**Related Components:**
- [ ] Parent components still function
- [ ] Child components still load correctly
- [ ] Sibling components are unaffected
- [ ] Template includes work properly

**Data Integrity:**
- [ ] Database operations complete successfully
- [ ] Model relationships are maintained
- [ ] Validation doesn't break existing data
- [ ] Cache invalidation works correctly

**User Experience:**
- [ ] UI displays correctly
- [ ] Error messages are user-friendly
- [ ] Success feedback is shown
- [ ] Navigation remains functional

### Example Side Effect Check

```php
// If you modified User model, check these areas:

// 1. Components that display user data
$affectedComponents = [
    'UserProfile',
    'UserCard', 
    'UserList',
    'Dashboard' // May show user info
];

// 2. Templates that include user data
$affectedTemplates = [
    'UserProfile.tpl',
    'Header.tpl', // May show username
    'Navigation.tpl' // May show user menu
];

// 3. Models with user relationships
$relatedModels = [
    'Article', // Has authorId -> User
    'Comment', // Has userId -> User  
    'Message'  // Has userId -> User
];
```

### Performance Impact Assessment

1. **Query Analysis:**
   - Did the fix add expensive database queries?
   - Are there N+1 query problems?
   - Is proper indexing in place?

2. **Caching Impact:**
   - Does the fix invalidate important caches?
   - Are new cache strategies needed?
   - Is cache warming required?

3. **Memory Usage:**
   - Does the fix load more data into memory?
   - Are there potential memory leaks?
   - Is pagination needed for large datasets?

## Step 5: Final Review Preparation

### Documentation for End-User Review

```markdown
## Fix Implementation Summary

**Issue:** [Original problem description]

**Root Cause:** [Technical explanation of what was wrong]

**Solution Applied:**
- [Specific change 1]
- [Specific change 2] 
- [Specific change 3]

**Files Modified:**
- `src/[path]/[file].php` - [Description of changes]
- `src/[path]/[template].tpl` - [Description of changes]

**Testing Completed:**
- ✅ Original issue resolved
- ✅ Related functionality tested
- ✅ Side effects checked
- ✅ Performance verified

**Side Effects Analysis:**
- **Components Checked:** [List of components tested]
- **Impact Assessment:** [None/Minor/Significant]
- **Additional Notes:** [Any concerns or recommendations]

**Ready for Review:**
The fix is ready for your testing and approval. Please verify:
1. The original issue is resolved
2. Normal workflow still functions correctly
3. No new issues have been introduced
```

### Pre-Review Checklist

**Code Quality:**
- [ ] Code follows TN Framework patterns
- [ ] Proper error handling implemented
- [ ] Input validation added where needed
- [ ] Debug logging can be easily removed/disabled

**Documentation:**
- [ ] Changes are clearly explained
- [ ] Side effects are documented
- [ ] Testing approach is described
- [ ] Known limitations are noted

**Completeness:**
- [ ] All aspects of the issue are addressed
- [ ] No obvious edge cases are missed
- [ ] Error messages are user-friendly
- [ ] Success feedback is appropriate

## Common Debugging Patterns

### Model Issues
```php
// Debug model creation/updates
error_log("Creating model with data: " . print_r($data, true));
$model = Model::getInstance();
error_log("Model instance created with ID: " . ($model->id ?? 'none'));
```

### Component Issues
```php
// Debug component preparation
error_log("Component::prepare() - Route params: " . print_r($this->getRouteParams(), true));
error_log("Component::prepare() - User ID: " . User::getActive()->id);
```

### Template Issues
```smarty
{* Debug template variables *}
{debug}

{* Conditional debugging *}
{if $smarty.const.DEBUG_MODE}
    <pre>{$debugData|print_r}</pre>
{/if}
```

### Database Issues
```php
// Debug database queries
$results = Model::searchByProperties($criteria);
error_log("Query returned " . count($results) . " results");
error_log("Query criteria: " . print_r($criteria, true));
```

## Emergency Debugging

For critical production issues:

1. **Immediate Logging:**
   ```php
   error_log("CRITICAL: " . date('Y-m-d H:i:s') . " - Issue description");
   ```

2. **Quick Diagnosis:**
   - Check recent deployments
   - Review error logs for patterns
   - Identify affected user groups

3. **Rapid Fix:**
   - Apply minimal viable fix
   - Add extensive logging
   - Plan proper fix for later

4. **Monitor:**
   - Watch error logs closely
   - Verify fix effectiveness
   - Prepare rollback if needed 