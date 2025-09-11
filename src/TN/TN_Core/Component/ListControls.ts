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
        this.$filterCount = this.$element.find('.filter-count');
        
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
        const currentValue = this.getCurrentComponentValue();
        console.log('🔍 ListControls initializeComponentValue:', currentValue);
        this.$element.attr('data-value', JSON.stringify(currentValue));
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
     * Get default sort from template (data-default="true") or first button
     */
    private getDefaultSort(): string {
        const $defaultSort = this.$sortButtons.filter('[data-default="true"]');
        if ($defaultSort.length > 0) {
            return $defaultSort.data('sort');
        }
        return this.$sortButtons.first().data('sort') || 'popular';
    }

    /**
     * Get default display from template (data-default="true") or first button
     */
    private getDefaultDisplay(): string {
        const $defaultDisplay = this.$displayButtons.filter('[data-default="true"]');
        if ($defaultDisplay.length > 0) {
            return $defaultDisplay.data('display');
        }
        return this.$displayButtons.first().data('display') || 'grid';
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
     * Update sort button visual states
     */
    private updateSortButtonStates(activeSort: string): void {
        this.$sortButtons.each((index: number, element: HTMLElement) => {
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

        const $activeButton = this.$sortButtons.filter(`[data-sort="${activeSort}"]`);
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
     * Update display button visual states
     */
    private updateDisplayButtonStates(activeDisplay: string): void {
        this.$displayButtons.each((index: number, element: HTMLElement) => {
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

        const $activeButton = this.$displayButtons.filter(`[data-display="${activeDisplay}"]`);
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
     * Update sort value and trigger change
     */
    private updateSort(sort: string): void {
        const currentValue = this.getCurrentComponentValue();
        currentValue.sort = sort;
        this.setComponentValue(currentValue);
    }

    /**
     * Update display value and trigger change
     */
    private updateDisplay(display: string): void {
        const currentValue = this.getCurrentComponentValue();
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
        
        // Get all filter input names from template
        const filterParams: string[] = [];
        this.$filterInputs.each((index: number, element: HTMLInputElement) => {
            const name = element.name;
            if (name) {
                filterParams.push(name);
            }
        });
        
        // Update all filter values - only include non-empty values
        filterParams.forEach(param => {
            const inputValue = this.getInputValue(param);
            if (inputValue && inputValue.trim() !== '') {
                currentValue[param] = inputValue.trim();
            } else {
                delete currentValue[param];
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
        const element = $(`[name="${name}"]`)[0] as HTMLInputElement | HTMLSelectElement;
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
     * Get current component value from form state
     */
    private getCurrentComponentValue(): Record<string, any> {
        const value: Record<string, any> = {};
        
        console.log('🔍 getCurrentComponentValue - Starting detection...');
        
        // Get current sort from active button or dropdown
        const $activeSort = this.$sortButtons.filter('[class*="sort-button-active"]');
        console.log('🔍 Active sort buttons found:', $activeSort.length);
        if ($activeSort.length > 0) {
            value.sort = $activeSort.data('sort') as string;
        } else {
            // Check for dropdown value
            const $sortDropdown = this.$element.find('[data-sort-dropdown]');
            console.log('🔍 Sort dropdowns found:', $sortDropdown.length);
            if ($sortDropdown.length > 0) {
                const dropdownValue = $sortDropdown.val() as string;
                console.log('🔍 Sort dropdown value:', dropdownValue);
                if (dropdownValue) {
                    value.sort = dropdownValue;
                }
            }
        }
        
        // Get current display from active button or dropdown
        const $activeDisplay = this.$displayButtons.filter('[class*="sort-button-active"], [class*="active"]');
        console.log('🔍 Active display buttons found:', $activeDisplay.length);
        if ($activeDisplay.length > 0) {
            value.viewMode = $activeDisplay.data('display') as string;
            console.log('🔍 ViewMode from active button:', value.viewMode);
        } else {
            // Check for dropdown value
            const $displayDropdown = this.$element.find('[data-display-dropdown]');
            console.log('🔍 Display dropdowns found:', $displayDropdown.length);
            if ($displayDropdown.length > 0) {
                const dropdownValue = $displayDropdown.val() as string;
                console.log('🔍 Display dropdown value:', dropdownValue);
                if (dropdownValue) {
                    value.viewMode = dropdownValue;
                    console.log('🔍 ViewMode set from dropdown:', value.viewMode);
                }
            }
        }
        
        // Get current filter values from inputs
        this.$filterInputs.each((index: number, element: HTMLInputElement) => {
            const name = element.name;
            const inputValue = element.value;
            if (name && inputValue && inputValue.trim() !== '') {
                value[name] = inputValue.trim();
            }
        });
        
        console.log('🔍 getCurrentComponentValue - Final value:', value);
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
     * Update filter badge count
     */
    private updateFilterBadge(): void {
        let activeFilters = 0;
        
        this.$filterInputs.each((index: number, element: HTMLInputElement) => {
            if (element.value?.trim()) {
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
