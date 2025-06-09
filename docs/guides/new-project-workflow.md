# New Project Development Workflow Guide

## Overview

This guide provides a systematic approach for developing new projects in the TN Framework, from initial spec analysis through end-user testing. Projects can range from single features to complete websites, with each page/route handled as a separate implementation cycle.

## Project Structure

### Directory Organization

```
_dev/
└── project-name/
    ├── 01-landing-page/
    │   └── spec.md
    ├── 02-signup-flow/
    │   └── spec.md
    ├── 03-dashboard/
    │   └── spec.md
    └── assets/
        └── figma-exports/
```

### Asset Storage

All project assets (images, icons, graphics) should be stored in:
```
public/fbgstatic/img/project-name/
├── landing-page/
├── dashboard/
└── shared/
```

## Development Process Overview

Each page/route follows this 5-step process:

1. **Component Setup** - Create base components using established patterns
2. **Data Requirements** - Identify and approve model layer variables needed
3. **Data Layer Implementation** - Build model classes and data access logic
4. **Template Implementation** - Build UI templates block by block using Figma designs
5. **End-User Testing** - Browser testing and validation

## Step 1: Project Initialization

### Analyze the Spec

Start by thoroughly reading the spec file (`_dev/project-name/01-page-name/spec.md`):

**Key Elements to Identify:**
- Page sections and their purposes
- Interactive elements (forms, buttons, CTAs)
- Data requirements (user info, lists, calculations)
- Special functionality (authentication, API calls, real-time updates)
- Responsive design requirements

### Create Initial To-Do List

Create a comprehensive markdown checklist in the page directory (`_dev/project-name/01-page-name/implementation-todo.md`):

```markdown
## Landing Page Implementation To-Do

### Phase 1: Component Setup
- [ ] Create LandingPage main component (following HTML Components guide)
- [ ] Set up controller routing for landing page
- [ ] Create component reload route

### Phase 2: Data Requirements Analysis
- [ ] Identify hero section data needs
- [ ] Identify content preview data requirements
- [ ] Identify testimonials/trust signal data needs
- [ ] Get user approval for data layer variables

### Phase 3: Data Layer Implementation
- [ ] Create SampleData model class
- [ ] Create Testimonial model class
- [ ] Implement hero section data access methods
- [ ] Implement sample data generation logic

### Phase 4: Template Implementation
- [ ] Build hero section template block
- [ ] Build features section template block
- [ ] Build content preview template block
- [ ] Build testimonials section template block
- [ ] Build footer CTA template block

### Phase 5: Testing & Validation
- [ ] End-user browser testing
- [ ] Responsive design validation
- [ ] CTA functionality testing
```

**Granularity Guidelines:**
- **Single task** if covered by existing guide (e.g., "Create HTML Component" references HTML Components guide)
- **Multiple granular tasks** for complex model layer work or custom functionality
- **One template block per task** for UI implementation

### Identify Dependencies

Before starting implementation, identify shared components or models needed across multiple pages:

```markdown
## Shared Dependencies
- [ ] UserAuthComponent (used by landing, signup, dashboard)
- [ ] ContentPreviewComponent (used by landing, dashboard)
- [ ] User model extensions (used across all pages)
```

**Rule:** Always implement shared dependencies first before page-specific components.

## Step 2: Component Setup

### Create Base Components

Follow the [HTML Components guide](../reference/components-html.md) exactly for component creation:

1. **Propose component name and confirm location**
2. **Create controller route** (both page route and reload route)
3. **Create component directory structure**
4. **Create component PHP class**
5. **Create basic template structure**
6. **Add TypeScript file if needed**
7. **Add SCSS file if needed**
8. **Update component maps**

**Example Implementation:**

```php
<?php
namespace Company\ProjectName\Component\Landing;

use TN\TN_Core\Component\HTMLComponent;

class LandingPage extends HTMLComponent
{
    public function prepare(): void
    {
        // Data loading will be added in Step 3
        $this->reloadRoute = 'projectName.landingPageComponent';
    }
    
    public function getTemplateVariables(): array
    {
        return [
            // Variables will be added as we identify them
        ];
    }
}
```

**Update To-Do List:** Mark component setup tasks as complete:
```markdown
- [x] Create LandingPage main component (following HTML Components guide)
- [x] Set up controller routing for landing page
- [x] Create component reload route
```

## Step 3: Data Requirements Analysis

### Connect to Figma for UI Analysis

Use Figma MCP to analyze the design and identify data needs:

1. **User selects the page design in Figma**
2. **Retrieve design via MCP:**
   ```
   Get code and image from Figma MCP
   Analyze UI elements for data requirements
   ```

3. **Download and store assets:**
   ```bash
   # Store assets in project directory
   public/fbgstatic/img/project-name/landing-page/
   ├── hero-background.jpg
   ├── sample-report-preview.png
   ├── step-icons/
   └── testimonial-avatars/
   ```

### Identify Required Variables

Based on Figma design analysis, identify all data the component needs:

**Example Data Requirements:**

```markdown
## Data Layer Variables Needed

### Hero Section
- `heroHeadline` (string) - Main headline text
- `heroSubheadline` (string) - Supporting description
- `ctaButtonText` (string) - Primary CTA button text
- `ctaButtonUrl` (string) - CTA destination URL

### Content Preview
- `sampleContent` (SampleData object) - Mock content data including:
  - `contentScore` (string) - e.g., "A-" or "82/100"
  - `featuredItem` (Item object) - Top item highlight
  - `improvementNote` (string) - Area for improvement
  - `previewImage` (string) - Path to preview graphic

### How It Works Section
- `processSteps` (array of Step objects) - 3-step process including:
  - `stepNumber` (int)
  - `stepTitle` (string)
  - `stepDescription` (string)
  - `stepIcon` (string) - Icon path

### Trust Signals
- `testimonials` (array of Testimonial objects) - User testimonials including:
  - `quote` (string)
  - `author` (string)
  - `userInfo` (string)
  - `avatar` (string) - Avatar image path
- `trustStats` (array) - Stat badges like "100,000+ users"
```

### Request User Approval

Present the complete data requirements for approval:

```markdown
## Data Layer Variables for Landing Page Component

Based on the Figma design analysis, the LandingPage component will need these variables:

[Present full variable list with explanations]

**New Model Classes Proposed:**
- `SampleData` - Represents mock content data for preview
- `Step` - Represents individual steps in process flow
- `Testimonial` - Represents user testimonials

**Questions for Approval:**
1. Do these variables cover all data needs shown in the design?
2. Should any of these be properties of existing models instead of new ones?
3. Are there additional data points we should capture for future features?
4. Should we proceed with implementing these model classes?
```

**Wait for user approval before proceeding to Step 4.**

## Step 4: Data Layer Implementation

### Create New Model Classes

Based on approved data requirements, implement the model layer:

**Example: SampleData Model**

```php
<?php
namespace Company\ProjectName\Model\Content;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Constraints\Strlen;

#[TableName('sample_data')]
class SampleData implements Persistence
{
    use MySQL;
    use PersistentModel;
    
    #[Strlen(max: 10)]
    public string $contentScore;
    
    #[Strlen(max: 255)]
    public string $featuredItemName;
    
    #[Strlen(max: 500)]
    public string $featuredItemHighlight;
    
    #[Strlen(max: 500)]
    public string $improvementNote;
    
    #[Strlen(max: 255)]
    public string $previewImage;
    
    public bool $isActive = true;
}
```

### Implement Data Access Methods

Add methods to load and provide data to the component:

```php
<?php
namespace Company\ProjectName\Component\Landing;

use TN\TN_Core\Component\HTMLComponent;
use Company\ProjectName\Model\Content\SampleData;
use Company\ProjectName\Model\Content\Testimonial;

class LandingPage extends HTMLComponent
{
    private ?SampleData $sampleContent = null;
    private array $testimonials = [];
    private array $processSteps = [];
    
    public function prepare(): void
    {
        $this->loadSampleContent();
        $this->loadTestimonials();
        $this->loadProcessSteps();
        $this->reloadRoute = 'projectName.landingPageComponent';
    }
    
    private function loadSampleContent(): void
    {
        $content = SampleData::searchByProperties([
            'isActive' => true,
            'limit' => 1
        ]);
        
        $this->sampleContent = $content[0] ?? $this->createDefaultSampleContent();
    }
    
    private function createDefaultSampleContent(): SampleData
    {
        // Create default sample data if none exists
        $content = SampleData::getInstance();
        $content->contentScore = 'A-';
        $content->featuredItemName = 'Featured Item';
        $content->featuredItemHighlight = 'This is your top-performing item';
        $content->improvementNote = 'Consider improving this area';
        $content->previewImage = '/fbgstatic/img/project-name/landing-page/sample-content.png';
        return $content;
    }
    
    public function getTemplateVariables(): array
    {
        return [
            'heroHeadline' => 'Your Project Headline with AI-Powered Features!',
            'heroSubheadline' => 'See how your content performs with a custom analysis tailored to your needs.',
            'ctaButtonText' => 'Get Started Now',
            'ctaButtonUrl' => '/project-name/start',
            'sampleContent' => $this->sampleContent,
            'testimonials' => $this->testimonials,
            'processSteps' => $this->processSteps,
            'trustStats' => $this->getTrustStats()
        ];
    }
}
```

### Generate Database Schema

Update database schema for new models:

```bash
docker exec nginx php src/run.php schema/all
```

**Update To-Do List:**
```markdown
- [x] Create SampleData model class
- [x] Create Testimonial model class  
- [x] Implement hero section data access methods
- [x] Implement sample data generation logic
```

## Step 5: Template Implementation

### Figma Code Integration

**Check Framework Output:** If Figma exports anything other than Bootstrap, request framework change:

```markdown
The Figma design exported with Tailwind CSS classes. Please change your Figma settings to export Bootstrap code instead, then we'll proceed with template implementation.
```

### Block-by-Block Template Development

**Critical:** Implement templates one block/section at a time. Each block typically corresponds to:
- Content under a major heading
- Full-width sections of the page
- Distinct functional areas

**If unclear how to break down the page, ask the user for guidance.**

### Template Block Implementation Process

**Example: Hero Section Block**

1. **Identify the block boundaries:**
   ```smarty
   {* Hero Section - from top of page to end of main CTA *}
   ```

2. **Implement the block with Bootstrap classes:**
   ```smarty
   {* Hero Section Block *}
   <section class="hero-section bg-primary text-white py-5">
       <div class="container">
           <div class="row align-items-center">
               <div class="col-lg-6">
                   <h1 class="display-4 fw-bold mb-3">{$heroHeadline}</h1>
                   <p class="lead mb-4">{$heroSubheadline}</p>
                   <a href="{$ctaButtonUrl}" class="btn btn-warning btn-lg px-4 py-2">
                       {$ctaButtonText}
                   </a>
               </div>
               <div class="col-lg-6">
                   <img src="{$sampleContent->previewImage}" 
                        alt="Sample Content Preview" 
                        class="img-fluid rounded shadow">
               </div>
           </div>
       </div>
   </section>
   ```

3. **Request approval for this block before proceeding:**
   ```markdown
   ## Hero Section Template Block
   
   I've implemented the hero section template block with:
   - Responsive 2-column layout
   - Dynamic headline and subheadline from component variables
   - Primary CTA button linking to the start process
   - Sample content preview image
   - Bootstrap utility classes for styling
   
   [Show full template code]
   
   Please review and approve this block before I proceed to the "Features" section.
   ```

4. **After approval, move to next block and repeat process**

### Complete Template Structure

```smarty
{* src/Company/ProjectName/Component/Landing/LandingPage/LandingPage.tpl *}
<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    
    {* Hero Section Block - Implemented first *}
    <section class="hero-section">
        {* Hero content here *}
    </section>
    
    {* Features Section Block - Implemented second *}
    <section class="features-section">
        {* Features content here *}
    </section>
    
    {* Content Preview Block - Implemented third *}
    <section class="content-preview-section">
        {* Content preview here *}
    </section>
    
    {* Testimonials Section Block - Implemented fourth *}
    <section class="testimonials-section">
        {* Testimonials content here *}
    </section>
    
    {* Footer CTA Block - Implemented fifth *}
    <section class="footer-cta-section">
        {* Footer CTA content here *}
    </section>
    
</div>
```

### Template Best Practices

1. **Always escape user content:** `{$variable|escape}`
2. **Use semantic HTML:** `<section>`, `<article>`, `<header>`, etc.
3. **Include proper Bootstrap classes:** Use grid system, utilities, components
4. **Mobile-first responsive design:** Use Bootstrap responsive classes
5. **Accessibility:** Include proper alt texts, ARIA labels, semantic markup

**Update To-Do List after each block:**
```markdown
- [x] Build hero section template block
- [ ] Build how-it-works section template block
- [ ] Build sample report preview template block
- [ ] Build testimonials section template block
- [ ] Build footer CTA template block
```

## Step 6: End-User Testing

### Prepare for Testing

1. **Ensure component is accessible via browser:**
   ```
   http://your-domain.com/project-name/landing
   ```

2. **Verify all assets are loading correctly:**
   - Images from `public/fbgstatic/img/project-name/`
   - CSS styles applying properly
   - Interactive elements functioning

3. **Check responsive design:**
   - Desktop layout
   - Tablet layout  
   - Mobile layout

### Testing Checklist

Present this checklist to the user for browser testing:

```markdown
## End-User Testing Checklist

### Visual Design
- [ ] Page loads without errors
- [ ] All images display correctly
- [ ] Text is readable and properly styled
- [ ] Layout matches Figma design expectations
- [ ] Colors and typography are consistent

### Responsive Design
- [ ] Desktop view (1200px+) displays correctly
- [ ] Tablet view (768px-1199px) displays correctly
- [ ] Mobile view (767px and below) displays correctly
- [ ] No horizontal scrolling on mobile
- [ ] Touch targets are appropriately sized

### Functionality
- [ ] Primary CTA button links correctly
- [ ] Secondary CTA button (if present) links correctly
- [ ] All navigation elements work as expected
- [ ] Component reloading works (if applicable)
- [ ] Form submissions work (if applicable)

### Content
- [ ] All text content displays from component variables
- [ ] Dynamic data loads correctly
- [ ] Sample data appears as expected
- [ ] No placeholder text or missing content

### Performance
- [ ] Page loads quickly
- [ ] Images are optimized and load promptly
- [ ] No console errors in browser developer tools
- [ ] Smooth scrolling and interactions
```

### Issue Resolution

If issues are found during testing:

1. **Document the issue clearly:**
   ```markdown
   **Issue:** CTA button not linking correctly on mobile
   **Expected:** Should navigate to /project-name/start
   **Actual:** Button click does nothing
   **Browser:** Safari iOS 15
   ```

2. **Follow debugging workflow:** Reference [Debugging Workflow Guide](debugging-workflow.md) for systematic issue resolution

3. **Update and re-test:** Fix issues and repeat testing process

### Final Validation

Once all testing passes:

```markdown
## Implementation Complete

### ✅ Landing Page Successfully Implemented

**Components Created:**
- LandingPage main component with full functionality
- Associated controller routes and reload capability

**Data Layer:**
- SampleData model with proper schema
- Testimonial model with content management
- Complete data access methods

**Template:**
- Fully responsive Bootstrap-based template
- All 5 template blocks implemented and approved
- Mobile-optimized design matching Figma specifications

**Testing:**
- All browser testing completed successfully
- Responsive design validated across devices
- CTA functionality verified
- Performance acceptable

**Ready for:** Next page implementation or project handoff
```

## Common Patterns & Tips

### Multi-Page Projects

For projects with multiple pages:

1. **Implement shared components first:**
   ```markdown
   ## Project: My Project (3 pages)
   
   ### Shared Components (Implement First)
   - [ ] Header/Navigation component
   - [ ] Footer component  
   - [ ] User authentication flow
   
   ### Page-Specific Implementation
   - [ ] Landing page (01-landing-page/)
   - [ ] Signup flow (02-signup-flow/)
   - [ ] Dashboard (03-dashboard/)
   ```

2. **Reuse patterns across pages:**
   - Standard controller routing patterns
   - Consistent template block structure
   - Shared CSS/SCSS variables

### Complex Data Requirements

For pages with complex data needs:

1. **Break model layer into multiple to-do items:**
   ```markdown
   ### Data Layer Implementation
   - [ ] Create User model extensions for profile data
   - [ ] Create Content model with calculation methods
   - [ ] Create Settings model with configuration management
   - [ ] Implement content generation algorithm
   - [ ] Add caching for expensive calculations
   - [ ] Create data migration scripts for existing users
   ```

2. **Consider performance implications:**
   - Database query optimization
   - Caching strategies
   - Lazy loading for expensive operations

### Reusable Components

When building components that will be reused:

1. **Design for flexibility:**
   ```php
   // Accept configuration parameters
   public function __construct(array $config = [])
   {
       $this->showAuthor = $config['showAuthor'] ?? true;
       $this->maxItems = $config['maxItems'] ?? 10;
   }
   ```

2. **Create clear documentation:**
   ```markdown
   ## ContentPreviewComponent Usage
   
   **Purpose:** Displays a sample content preview with configurable options
   
   **Configuration:**
   - `showScore` (bool) - Whether to show content score
   - `showHighlight` (bool) - Whether to show featured item
   - `contentType` (string) - 'sample' or 'real'
   ```

### Error Handling

Always include proper error handling:

```php
public function prepare(): void
{
    try {
        $this->loadSampleContent();
        $this->loadTestimonials();
    } catch (Exception $e) {
        error_log('LandingPage::prepare() error: ' . $e->getMessage());
        // Set fallback data or error state
        $this->hasError = true;
        $this->errorMessage = 'Unable to load page content';
    }
}
```

## Project Completion

### Final Project Review

When all pages are complete:

1. **Verify cross-page functionality:**
   - Navigation between pages works
   - Shared components function consistently
   - User flow is smooth and logical

2. **Performance check:**
   - All pages load acceptably
   - Database queries are optimized
   - Images and assets are optimized

3. **Documentation:**
   - Update project README if needed
   - Document any custom patterns used
   - Note any future enhancement opportunities

### Handoff Preparation

Prepare comprehensive handoff documentation:

```markdown
## Project: My Project - Implementation Complete

### Pages Implemented
- Landing Page (`/project-name/landing`)
- Signup Flow (`/project-name/signup`) 
- Dashboard (`/project-name/dashboard`)

### Components Created
- LandingPage - Main landing page component
- SignupForm - Multi-step signup process
- Dashboard - User dashboard
- ContentPreview - Reusable content preview component

### Models Created
- SampleData - Mock content data for previews
- UserContent - Actual generated user content
- Settings - Application settings and configuration
- Testimonial - User testimonial management

### Database Changes
- New tables: sample_data, user_content, settings, testimonials
- Schema generated and ready for production

### Assets
- All images stored in `public/fbgstatic/img/project-name/`
- Optimized for web delivery
- Responsive image variants included

### Testing Status
- All pages browser tested successfully
- Responsive design verified
- Cross-browser compatibility checked
- Performance acceptable

### Next Steps
- Ready for staging deployment
- Marketing team can create additional landing pages using components
- User onboarding flow is complete and functional
```

This comprehensive workflow ensures consistent, high-quality implementation of new projects while maintaining the TN Framework's patterns and best practices. 