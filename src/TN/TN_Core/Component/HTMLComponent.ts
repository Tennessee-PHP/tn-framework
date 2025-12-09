import $, { Cash } from 'cash-dom';
import axios, { AxiosError, AxiosResponse } from 'axios';
import ComponentFactory from './ComponentFactory';
import ErrorToast from './Toast/ErrorToast';
import PageSingleton from '@tn/TN_Core/Component/Renderer/Page/PageSingleton';
import Page from './Renderer/Page/Page';
import _ from 'lodash';
import { IComponentFactory } from './IComponentFactory';

/**
 * Data to be sent to the server when reloading the component
 */
interface ReloadData {
    [key: string]: string | string[] | number | boolean;
}

type ReloadMethod = 'get' | 'post';

let globalI: number = 0;

/**
 * Base class for all components including reload functionality
 */
abstract class HTMLComponent {
    protected $element: Cash;
    protected reloadMethod: ReloadMethod = 'get';
    protected updateUrlQueryOnReload: boolean = false;
    protected scrollToTopOnReload: boolean = false;
    protected controls: Cash[] = [];
    protected i: number;
    protected reloading: Boolean = false;
    private triggeringControl: Cash | null = null;
    private reloadTimer: ReturnType<typeof setTimeout> | null = null;
    private keyUpReloadDelay: number = 500;
    private reloadCount: number = 0;
    private cloudflareTurnstileToken: string;
    private initialControlsState: string;
    static componentFactory: IComponentFactory;

    constructor($element: Cash) {
        this.$element = $element;
        this.i = globalI;
        globalI += 1;

        if (this.$element.data('cloudflare-turnstile') === 'on') {
            this.setupCloudflareTurnstile();
        }

        this.$element.on('reload', this.triggerReload.bind(this));
        
        this.observe();
        
        // Capture initial controls state AFTER observe() sets up controls
        this.initialControlsState = this.getControlsStateHash();
    }

    protected triggerReload(e: Event): void {
        e.preventDefault();
        e.stopPropagation();
        this.reload();
    }

    static setComponentFactory(factory: IComponentFactory): void {
        HTMLComponent.componentFactory = factory;
    }

    protected setupCloudflareTurnstile(): void {
        // @ts-ignore
        turnstile.ready(() => {
            // @ts-ignore
            turnstile.render(this.$element.find('.cloudflare-turnstile-container').get(0), {
                // @ts-ignore
                sitekey: TN.CLOUDFLARE_TURNSTILE_SITE_KEY,
                callback: (token: string) => {
                    this.cloudflareTurnstileToken = token;
                },
            });
        });
    }

    protected abstract observe(): void;

    protected unobserve(): void {}

    protected observeControls(): void {
        this.controls.forEach((control: Cash) => {
                control.on('change', this.onControlChange.bind(this, control));
                if (control.is('input[type=text], input[type=password], input[type=email]')) {
                    control.on('keyup', this.onControlKeyUp.bind(this));
                }
        });
    }

    private onControlChange($control: Cash): void {
        // Set timestamp when control changes and store reference to triggering control
        const timestamp = Date.now();
        $control.attr('data-timestamp', timestamp.toString());
        this.triggeringControl = $control;
        
        // For el-select, try to read value directly from selected option first
        const element = $control[0];
        let currentValue: any = undefined;
        
        if (element && element.tagName === 'EL-SELECT') {
            // Try to get value from element's value property first
            const elSelectElement = element as any; // Custom web component, cast to any to access value property
            if (elSelectElement.value !== undefined && elSelectElement.value !== null) {
                currentValue = elSelectElement.value;
            } else {
                // Fallback: look for newly selected option
                const selectedOption = element.querySelector('el-option[selected], el-option[aria-selected="true"]') as HTMLElement;
                if (selectedOption) {
                    const value = selectedOption.getAttribute('value');
                    if (value !== null) {
                        currentValue = value;
                    }
                }
            }
        } else {
            // Standard controls
            currentValue = this.getControlValueFromElement($control);
        }
        
        // Store the value in data-value attribute so it persists through reloads
        // Note: empty string is a valid value (e.g., "All Sports" filter)
        if (typeof currentValue !== 'undefined' && currentValue !== null) {
            $control.data('value', currentValue);
        }
        
        // @ts-ignore
        this.reload();
    }

    onControlKeyUp(e: any): void {
        const $input = $(e.currentTarget);
        if (this.reloadTimer) {
            clearTimeout(this.reloadTimer);
        }
        this.reloadTimer = setTimeout(this.reload.bind(this), this.keyUpReloadDelay);
    }

    protected getReloadData(): ReloadData {
        // Collect all control values first
        const data = this.collectControlValues();
        
        // Reset page if other controls changed
        if (data.page && data.page !== '1') {
            const currentControlsState = this.getControlsStateHash();
            if (currentControlsState !== this.initialControlsState) {
                data.page = '1';
            }
        }
        
        return data;
    }

    /**
     * Get the current value from a control, handling custom web components like el-select
     */
    private getControlValueFromElement($control: Cash): any {
        const element = $control[0];
        
        // Handle el-select custom web components
        if (element && element.tagName === 'EL-SELECT') {
            // First check data-value attribute (most reliable, set when change event fires)
            const dataValue = $control.data('value');
            if (dataValue !== undefined && dataValue !== null) {
                return dataValue;
            }
            
            // Try to get value from the element's value property (custom web component)
            // Note: This might be undefined if change event fired before value was updated
            const elSelectElement = element as any; // Custom web component, cast to any to access value property
            if (elSelectElement.value !== undefined && elSelectElement.value !== null && elSelectElement.value !== '') {
                return elSelectElement.value;
            }
            
            // Fallback: look for selected option in the DOM
            // Try different possible selectors for selected state
            let selectedOption = element.querySelector('el-option[selected]') as HTMLElement;
            if (!selectedOption) {
                selectedOption = element.querySelector('el-option[aria-selected="true"]') as HTMLElement;
            }
            if (!selectedOption) {
                // Look for option that has selected class or is marked as active
                selectedOption = element.querySelector('el-option.selected, el-option[class*="selected"]') as HTMLElement;
            }
            
            if (selectedOption) {
                const value = selectedOption.getAttribute('value');
                if (value !== null) {
                    return value;
                }
            }
            
            // If no selected option found, return empty string (valid for "All" filters)
            return '';
        }
        
        // Standard controls: try .val() first
        let val = $control.val();
        if (typeof val === 'undefined' || val === null || val === '') {
            val = $control.data('value');
        }
        
        return val;
    }

    private collectControlValues(): ReloadData {
        const data: ReloadData = {
            componentIdNum: this.$element.attr('id').substring(4),
        };

        if (this.cloudflareTurnstileToken) {
            data['cloudflareTurnstileToken'] = this.cloudflareTurnstileToken;
        }

        // Track values by key, prioritizing controls with most recent timestamp
        const valuesByKey: Map<string, {value: any, timestamp: number, $control: Cash}> = new Map();

        // Process triggering control last so it takes priority
        const otherControls = this.controls.filter(c => !this.triggeringControl || c[0] !== this.triggeringControl[0]);
        const controlsToProcess = this.triggeringControl ? [...otherControls, this.triggeringControl] : this.controls;
        
        controlsToProcess.forEach(($control: Cash) => {
            let key = $control.data('request-key');

            // Skip controls without request-key unless they have request-unpack-value-from-json
            if (!key && $control.data('request-unpack-value-from-json') !== 'yes') {
                return;
            }
            
            // Get the value using our helper method that handles el-select
            let val = this.getControlValueFromElement($control);
            
            // For the triggering control, always prioritize the most recent data value
            if (this.triggeringControl && $control[0] === this.triggeringControl[0]) {
                const dataVal = $control.data('value');
                if (dataVal !== undefined && dataVal !== null) {
                    val = dataVal;
                }
            }
            
            // Skip only if value is truly undefined or null (empty string is valid for filters like "All Sports")
            if (typeof val === 'undefined' || val === null) {
                return;
            }
            
            if ($control.is('input[type=checkbox]')) {
                val = $control.prop('checked');
            }

            if ($control.data('request-unpack-value-from-json') === 'yes') {
                const parsedVal = typeof val === 'object' ? val : JSON.parse(val);
                _.assign(data, parsedVal);
            } else {
                // Get timestamp from the control
                const timestamp = parseInt($control.attr('data-timestamp') || '0', 10);

                // Check if we already have a value for this key
                const existing = valuesByKey.get(key);

                // If no existing value, or this control has a newer timestamp, use this value
                if (!existing || timestamp > existing.timestamp) {
                    valuesByKey.set(key, { value: val, timestamp, $control });
                }
            }
        });

        // Apply the values to the data object
        valuesByKey.forEach(({ value }, key) => {
            data[key] = value;
        });

        return data;
    }

    private getControlsStateHash(): string {
        const data = this.collectControlValues();
        const { componentIdNum, page, ...controlsData } = data;
        return JSON.stringify(controlsData, Object.keys(controlsData).sort());
    }

    protected setReloading(reloading: Boolean): void {
        this.reloading = reloading;
        this.$element.find('input, select, button').prop('disabled', reloading);
        this.$element.find('.component-loading').toggleClass('d-none', !reloading);
        this.$element.find('button[type="submit"] .spinner-border').toggleClass('d-none', !reloading);
    }

    protected reload(): void {
        if (this.reloading) {
            return;
        }

        this.setReloading(true);
        const reloadData = this.getReloadData();
        if (this.updateUrlQueryOnReload) {
            this.updateUrlQuery(reloadData);
        }

        reloadData['reload'] = 1;
        this.reloadCount += 1;

        if (this.reloadMethod === 'post') {
            this.postRequest(reloadData);
        } else if (this.reloadMethod === 'get') {
            this.getRequest(reloadData);
        }
    }

    protected postRequest(reloadData: ReloadData): void {
        axios
            .post(this.$element.data('reload-url'), reloadData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .catch(this.onReloadError.bind(this, this.reloadCount))
            .then(this.onReloadSuccess.bind(this, this.reloadCount));
    }

    protected getRequest(reloadData: ReloadData): void {
        axios
            .get(this.$element.data('reload-url'), {
                params: reloadData,
            })
            .then(this.onReloadSuccess.bind(this, this.reloadCount))
            .catch(this.onReloadError.bind(this, this.reloadCount));
    }

    protected updateUrlQuery(data: ReloadData): void {
        const thisUrl = new URL(document.location.href);
        let key: string;
        let keysToDelete: string[] = [];
        thisUrl.searchParams.forEach((value, key) => {
            keysToDelete.push(key);
        });

        keysToDelete.forEach(key => {
            thisUrl.searchParams.delete(key);
        });

        for (key in data) {
            // Skip internal parameters that should never appear in URL history
            if (key === 'componentIdNum' || key === 'reload' || key === 'more' || key === 'fromId') {
                continue;
            }
            let value: string;

            if (data[key] instanceof Array) {
                value = (data[key] as string[]).join(',');
            } else if (typeof data[key] === 'string') {
                value = data[key] as string;
            } else {
                // Convert non-string values to string safely
                value = String(data[key]);
            }

            // Skip empty values to keep URLs clean
            if (value && value.trim() !== '') {
                thisUrl.searchParams.set(key, value);
            }
        }
        history.pushState(null, '', thisUrl.href);
    }

    protected onReloadSuccess(reloadNumber: number, response: AxiosResponse): void {
        if (reloadNumber !== this.reloadCount) {
            return;
        }

        this.$element.off();

        this.controls.forEach((control: Cash) => {
            control.off();
        });

        if (this.reloadTimer) {
            clearTimeout(this.reloadTimer);
        }

        this.$element.hide();
        this.$element.before(response.data);
        let $newElement = this.$element.prev();
        this.$element.detach();
        this.unobserve();

        // instantiate new components inside here
        HTMLComponent.componentFactory.createComponent($newElement);
        $newElement.find('.tnc-component').each((i: number, element: Element) => {
            HTMLComponent.componentFactory.createComponent($(element));
        });

        let page: Page = PageSingleton();
        page.updated();

        // Scroll to top of component if enabled
        if (this.scrollToTopOnReload) {
            setTimeout(() => {
                if ($newElement && $newElement[0]) {
                    $newElement[0].scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 100); // Small delay to ensure DOM is updated
        }
    }

    protected onReloadError(reloadNumber: number, error: AxiosError): void {
        this.setReloading(false);
        // @ts-ignore
        new ErrorToast(error.response.data);
    }
}

export default HTMLComponent;
export { ReloadData, ReloadMethod };
