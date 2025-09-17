import $, { Cash } from 'cash-dom';
import HTMLComponent from './HTMLComponent';

/**
 * Base class for list controls that handle sorting, filtering, and display options
 * Configuration is driven by data attributes in the template
 */
export default abstract class ListControls extends HTMLComponent {
    // State management
    private allowChangeEvent: boolean = false;
    
    // Auto-discovered DOM elements
    private $sortButtons: Cash;
    private $displayButtons: Cash;
    private $filterButton: Cash;
    private $filterInputs: Cash;
    private $resetButton: Cash;
    private $applyButton: Cash;
    private $filterDrawer: Cash;
    private $filterBadge: Cash;
    private $filterCount: Cash;
    
    // State tracking
    private isApplyingFilters: boolean = false;

    protected observe(): void {
        this.discoverControlsFromTemplate();
        this.bindAllEvents();
        this.interceptChangeEvents();
        this.initializeComponentValue();
        this.initializeButtonStates();
        this.updateFilterBadge();
    }

    /**
     * Auto-discover all control elements from template using data attributes
     */
    private discoverControlsFromTemplate(): void {
        // Sort buttons - any button with data-sort attribute
        this.$sortButtons = this.$element.find('[data-sort]');
        
        // Sort dropdowns - any el-select with data-sort-dropdown attribute
        const $sortDropdowns = this.$element.find('[data-sort-dropdown]');
        
        // Display buttons - any button with data-display attribute  
        this.$displayButtons = this.$element.find('[data-display]');

        // Display dropdowns - any el-select with data-display-dropdown attribute
        const $displayDropdowns = this.$element.find('[data-display-dropdown]');
        
        // Filter elements
        this.$filterButton = this.$element.find('.filter-button');
        this.$filterBadge = this.$element.find('.filter-badge');
        this.$filterCount = this.$filterBadge; // The count goes directly in the badge
        
        // Filter drawer elements (global search since drawer is outside component)
        this.$filterInputs = $('.filter-input');
        this.$resetButton = $('.reset-filters');
        this.$applyButton = $('.apply-filters');
        this.$filterDrawer = $('[id$="-filters"]'); // Find drawer by ID pattern
        
        
        // Add sort dropdowns to sort buttons for unified handling
        this.$sortButtons = this.$sortButtons.add($sortDropdowns);
        // Add display dropdowns to display buttons for unified handling
        this.$displayButtons = this.$displayButtons.add($displayDropdowns);
    }

    /**
     * Bind events for all discovered controls
     */
    private bindAllEvents(): void {
        // Sort button events (includes both buttons and dropdowns)
        this.$sortButtons.on('click', this.handleSortClick.bind(this));
        this.$sortButtons.on('change', this.handleSortChange.bind(this));
        
        // Display button events (includes both buttons and dropdowns)
        this.$displayButtons.on('click', this.handleDisplayClick.bind(this));
        this.$displayButtons.on('change', this.handleDisplayChange.bind(this));
        
        // Filter events
        this.$applyButton.on('click', this.applyFilters.bind(this));
        this.$resetButton.on('click', this.resetFilters.bind(this));
        this.$filterInputs.on('keypress', this.handleFilterKeypress.bind(this));
        
        // Auto-apply filters when dialog closes
        const $filterDialog = $('#object-filters');
        if ($filterDialog.length > 0) {
            $filterDialog.on('close', this.applyFilters.bind(this));
        }
    }

    /**
     * Intercept change events to control when they fire
     */
    private interceptChangeEvents(): void {
        this.$element.on('change', this.handleComponentChange.bind(this));
        this.$element.find('input, select, textarea').on('change input blur', this.handleInputEvent.bind(this));
    }

    private handleComponentChange(e: Event): void {
        if (!this.allowChangeEvent) {
            e.stopImmediatePropagation();
            e.preventDefault();
        }
    }

    private handleInputEvent(e: Event): void {
        e.stopPropagation();
        e.stopImmediatePropagation();
    }

    /**
     * Initialize component value without firing change event
     */
    private initializeComponentValue(): void {
        // Only set data-value if it doesn't already exist (PHP component should set it)
        const existingValue = this.$element.attr('data-value');
        if (!existingValue || existingValue === 'null' || existingValue === '') {
            const currentValue = this.getCurrentComponentValue();
            this.$element.attr('data-value', JSON.stringify(currentValue));
        }
    }

    /**
     * Initialize button states based on current values
     */
    private initializeButtonStates(): void {
        const currentValue = this.getCurrentComponentValue();
        
        // Initialize sort buttons
        if (this.$sortButtons.length > 0) {
            const currentSort = currentValue.sort || this.getDefaultSort();
            this.updateSortButtonStates(currentSort);
        }
        
        // Initialize display buttons
        if (this.$displayButtons.length > 0) {
            const currentDisplay = currentValue.viewMode || currentValue.display || this.getDefaultDisplay();
            this.updateDisplayButtonStates(currentDisplay);
        }
    }

    /**
     * Get default value from template (data-default="true") or first button
     */
    private getDefaultValue($buttons: Cash, dataAttribute: string, fallback: string): string {
        const $defaultButton = $buttons.filter('[data-default="true"]');
        if ($defaultButton.length > 0) {
            return $defaultButton.data(dataAttribute.replace('data-', ''));
        }
        return $buttons.first().data(dataAttribute.replace('data-', '')) || fallback;
    }

    /**
     * Get default sort from template (data-default="true") or first button
     */
    private getDefaultSort(): string {
        return this.getDefaultValue(this.$sortButtons, 'data-sort', 'popular');
    }

    /**
     * Get default display from template (data-default="true") or first button
     */
    private getDefaultDisplay(): string {
        return this.getDefaultValue(this.$displayButtons, 'data-display', 'grid');
    }

    /**
     * Handle sort button clicks
     */
    private handleSortClick(e: Event): void {
        const $button = $(e.currentTarget as HTMLElement);
        const sort = $button.data('sort') as string;

        if (sort) {
            this.updateSortButtonStates(sort);
            this.updateSort(sort);
        }
    }

    /**
     * Handle sort dropdown changes
     */
    private handleSortChange(e: Event): void {
        const $dropdown = $(e.currentTarget as HTMLElement);
        const sort = $dropdown.val() as string;

        if (sort) {
            this.updateSort(sort);
        }
    }

    /**
     * Handle display dropdown changes
     */
    private handleDisplayChange(e: Event): void {
        const $dropdown = $(e.currentTarget as HTMLElement);
        const display = $dropdown.val() as string;

        if (display) {
            this.updateDisplay(display);
        }
    }

    /**
     * Handle display button clicks
     */
    private handleDisplayClick(e: Event): void {
        const $button = $(e.currentTarget as HTMLElement);
        const display = $button.data('display') as string;

        if (display) {
            this.updateDisplayButtonStates(display);
            this.updateDisplay(display);
        }
    }

    /**
     * Handle filter keypress (Enter to apply)
     */
    private handleFilterKeypress(e: KeyboardEvent): void {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.applyFilters();
        }
    }

    /**
     * Update button visual states for any control type
     */
    private updateButtonStates($buttons: Cash, activeValue: string, dataAttribute: string): void {
        // Reset all buttons to inactive state
        $buttons.each((index: number, element: HTMLElement) => {
            const $button = $(element);
            const activeClasses = $button.data('active-classes') || '';
            const inactiveClasses = $button.data('inactive-classes') || '';
            
            if (activeClasses) {
                $button.removeClass(activeClasses);
            }
            if (inactiveClasses) {
                $button.addClass(inactiveClasses);
            }
        });

        // Set active button to active state
        const $activeButton = $buttons.filter(`[${dataAttribute}="${activeValue}"]`);
        if ($activeButton.length > 0) {
            const activeClasses = $activeButton.data('active-classes') || '';
            const inactiveClasses = $activeButton.data('inactive-classes') || '';
            
            if (inactiveClasses) {
                $activeButton.removeClass(inactiveClasses);
            }
            if (activeClasses) {
                $activeButton.addClass(activeClasses);
            }
        }
    }

    /**
     * Update sort button visual states
     */
    private updateSortButtonStates(activeSort: string): void {
        this.updateButtonStates(this.$sortButtons, activeSort, 'data-sort');
    }

    /**
     * Update display button visual states
     */
    private updateDisplayButtonStates(activeDisplay: string): void {
        this.updateButtonStates(this.$displayButtons, activeDisplay, 'data-display');
    }

    /**
     * Update sort value and trigger change
     */
    private updateSort(sort: string): void {
        const currentValue = this.getCurrentComponentValueWithFilters();
        currentValue.sort = sort;
        this.setComponentValue(currentValue);
    }

    /**
     * Update display value and trigger change
     */
    private updateDisplay(display: string): void {
        const currentValue = this.getCurrentComponentValueWithFilters();
        // Use 'viewMode' for consistency with existing components
        currentValue.viewMode = display;
        this.setComponentValue(currentValue);
    }

    /**
     * Apply filters from drawer inputs
     */
    private applyFilters(): void {
        this.isApplyingFilters = true;
        
        const currentValue = this.getCurrentComponentValue();
        
        // Get unique filter parameter names from template
        const filterParams: string[] = [];
        const seenParams = new Set<string>();
        
        this.$filterInputs.each((index: number, element: HTMLElement) => {
            const name = element.getAttribute('name');
            if (name && !seenParams.has(name)) {
                filterParams.push(name);
                seenParams.add(name);
            }
        });
        
        // Update all filter values - only include non-empty values
        filterParams.forEach(param => {
            const inputValue = this.getInputValue(param);
            
            // Check if this is an exclusion parameter (data-exclude="true")
            const $input = this.$filterInputs.filter(`[name="${param}"]`).first();
            const isExclude = $input.data('exclude') === 'true' || $input.data('exclude') === true;
            
            if (isExclude) {
                // For exclusion inputs, collect unchecked values
                const allValues: string[] = [];
                const checkedValues: string[] = [];
                
                this.$filterInputs.filter(`[name="${param}"]`).each((index: number, element: HTMLInputElement) => {
                    allValues.push(element.value);
                    if (element.checked) {
                        checkedValues.push(element.value);
                    }
                });
                
                const uncheckedValues = allValues.filter(value => !checkedValues.includes(value));
                
                if (uncheckedValues.length > 0) {
                    currentValue[param] = uncheckedValues.join(',');
                } else {
                    delete currentValue[param];
                }
            } else {
                // Normal behavior for non-exclusion inputs
                const $inputs = this.$filterInputs.filter(`[name="${param}"]`);
                
                if ($inputs.first().is('input[type="checkbox"]')) {
                    // For normal checkboxes, collect checked values
                    const checkedValues: string[] = [];
                    $inputs.each((index: number, element: HTMLInputElement) => {
                        if (element.checked) {
                            checkedValues.push(element.value);
                        }
                    });
                    
                    if (checkedValues.length > 0) {
                        currentValue[param] = checkedValues.join(',');
                    } else {
                        delete currentValue[param];
                    }
                } else {
                    // For text inputs, selects, etc.
                    if (inputValue && inputValue.trim() !== '' && inputValue.trim() !== 'all') {
                        currentValue[param] = inputValue.trim();
                    } else {
                        delete currentValue[param];
                    }
                }
            }
        });

        this.setComponentValue(currentValue);
        this.closeFilterDrawer();
        
        setTimeout(() => {
            this.isApplyingFilters = false;
        }, 10);
    }

    /**
     * Reset all filters
     */
    private resetFilters(): void {
        this.isApplyingFilters = true;
        
        // Clear all input values in DOM
        this.$filterInputs.each((index: number, element: HTMLInputElement | HTMLSelectElement) => {
            element.value = '';
        });

        // Clear filter values from component state but preserve sort and display
        const currentValue = this.getCurrentComponentValue();
        const filterParams: string[] = [];
        this.$filterInputs.each((index: number, element: HTMLInputElement) => {
            const name = element.name;
            if (name) {
                filterParams.push(name);
            }
        });
        
        filterParams.forEach(param => {
            delete currentValue[param];
        });

        this.setComponentValue(currentValue);
        this.closeFilterDrawer();
        
        setTimeout(() => {
            this.isApplyingFilters = false;
        }, 10);
    }

    /**
     * Get input value by name
     */
    private getInputValue(name: string): string {
        // Find the element within our discovered filter inputs
        const $element = this.$filterInputs.filter(`[name="${name}"]`).first();
        if ($element.length === 0) {
            return '';
        }
        
        const element = $element[0] as HTMLInputElement | HTMLSelectElement;
        
        // For el-select elements, check if value is empty but there's a selected option
        if (element.tagName === 'EL-SELECT' && (!element.value || element.value === '')) {
            const selectedOption = element.querySelector('el-option[selected]') as HTMLElement;
            if (selectedOption) {
                const value = selectedOption.getAttribute('value');
                if (value) {
                    return value;
                }
            }
        }
        
        return element?.value ?? '';
    }

    /**
     * Close filter drawer
     */
    private closeFilterDrawer(): void {
        const drawer = this.$filterDrawer[0] as HTMLDialogElement;
        if (drawer && drawer.open) {
            drawer.close();
        }
    }

    /**
     * Get control value from active button or dropdown
     */
    private getControlValue($buttons: Cash, dataAttribute: string, dropdownSelector: string): string | undefined {
        // Check for active button first
        const $activeButton = $buttons.filter('[class*="active"]');
        if ($activeButton.length > 0) {
            return $activeButton.data(dataAttribute.replace('data-', '')) as string;
        }
        
        // Fall back to dropdown value
        const $dropdown = this.$element.find(dropdownSelector);
        if ($dropdown.length > 0) {
            const dropdownValue = $dropdown.val() as string;
            if (dropdownValue) {
                return dropdownValue;
            }
        }
        
        return undefined;
    }

    /**
     * Get current component value from form state
     */
    private getCurrentComponentValue(): Record<string, any> {
        // First try to get the current value from the component's data-value attribute
        // This preserves all existing values including filters
        const currentValueJson = this.$element.attr('data-value');
        let value: Record<string, any> = {};
        
        if (currentValueJson) {
            try {
                value = JSON.parse(currentValueJson);
            } catch (e) {
                // If parsing fails, start with empty object
                value = {};
            }
        }
        
        // Update with current DOM values for sort and display (these can change via UI)
        const sort = this.getControlValue(this.$sortButtons, 'data-sort', '[data-sort-dropdown]');
        if (sort) {
            value.sort = sort;
        }
        
        const display = this.getControlValue(this.$displayButtons, 'data-display', '[data-display-dropdown]');
        if (display) {
            value.viewMode = display;
        }
        
        return value;
    }

    /**
     * Get current component value including live filter values from DOM
     * Used when sort/display changes to ensure current filter state is preserved
     */
    private getCurrentComponentValueWithFilters(): Record<string, any> {
        const value = this.getCurrentComponentValue();
        
        // Get unique filter parameter names from template
        const filterParams: string[] = [];
        const seenParams = new Set<string>();
        
        this.$filterInputs.each((index: number, element: HTMLElement) => {
            const name = element.getAttribute('name');
            if (name && !seenParams.has(name)) {
                filterParams.push(name);
                seenParams.add(name);
            }
        });
        
        // Update all filter values from current DOM state - only include non-empty values
        filterParams.forEach(param => {
            const inputValue = this.getInputValue(param);
            
            // Check if this is an exclusion parameter (data-exclude="true")
            const $input = this.$filterInputs.filter(`[name="${param}"]`).first();
            const isExclude = $input.data('exclude') === 'true' || $input.data('exclude') === true;
            
            if (isExclude) {
                // For exclusion inputs, collect unchecked values
                const allValues: string[] = [];
                const checkedValues: string[] = [];
                
                this.$filterInputs.filter(`[name="${param}"]`).each((index: number, element: HTMLInputElement) => {
                    allValues.push(element.value);
                    if (element.checked) {
                        checkedValues.push(element.value);
                    }
                });
                
                const uncheckedValues = allValues.filter(value => !checkedValues.includes(value));
                
                if (uncheckedValues.length > 0) {
                    value[param] = uncheckedValues.join(',');
                } else {
                    delete value[param];
                }
            } else {
                // Normal behavior for non-exclusion inputs
                const $inputs = this.$filterInputs.filter(`[name="${param}"]`);
                
                if ($inputs.first().is('input[type="checkbox"]')) {
                    // For normal checkboxes, collect checked values
                    const checkedValues: string[] = [];
                    $inputs.each((index: number, element: HTMLInputElement) => {
                        if (element.checked) {
                            checkedValues.push(element.value);
                        }
                    });
                    
                    if (checkedValues.length > 0) {
                        value[param] = checkedValues.join(',');
                    } else {
                        delete value[param];
                    }
                } else {
                    // For regular inputs (text, select, etc.)
                    if (inputValue && inputValue !== '' && inputValue !== 'all') {
                        value[param] = inputValue;
                    } else {
                        delete value[param];
                    }
                }
            }
        });
        
        return value;
    }

    /**
     * Set component value and trigger controlled change event
     */
    private setComponentValue(value: Record<string, any>): void {
        const currentValueJson = this.$element.attr('data-value') || '{}';
        const newValueJson = JSON.stringify(value);
        
        if (currentValueJson === newValueJson) {
            this.closeFilterDrawer();
            return;
        }
        
        this.$element.attr('data-value', newValueJson);
        this.closeFilterDrawer();
        this.updateFilterBadge();
        
        // Allow the change event to fire, then immediately block future ones
        this.allowChangeEvent = true;
        this.$element.trigger('change');
        this.allowChangeEvent = false;
    }

    /**
     * Update filter badge count based on component value
     */
    private updateFilterBadge(): void {
        const dataValueAttr = this.$element.attr('data-value') || '{}';
        const currentValue = JSON.parse(dataValueAttr);
        
        // Count filter parameters (exclude sort and viewMode which aren't filters)
        const filterKeys = Object.keys(currentValue).filter(key => 
            key !== 'sort' && key !== 'viewMode'
        );
        
        // For exclusion parameters, count the number of excluded items
        let activeFilters = 0;
        filterKeys.forEach(key => {
            const value = currentValue[key];
            
            if (typeof value === 'string' && value.includes(',')) {
                // Comma-separated values (like excludeTypes=type1,type2) - count each item
                const count = value.split(',').length;
                activeFilters += count;
            } else if (value && value !== '' && value !== 'all') {
                // Single filter value
                activeFilters++;
            }
        });

        if (activeFilters > 0) {
            this.$filterCount.text(activeFilters.toString());
            this.$filterBadge.show();
        } else {
            this.$filterBadge.hide();
        }
    }
}
