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
    protected controls: Cash[] = [];
    protected i: number = 0;
    protected reloading: Boolean = false;
    private reloadTimer: ReturnType<typeof setTimeout> | null = null;
    private keyUpReloadDelay: number = 500;
    private reloadCount: number = 0;
    private cloudflareTurnstileToken: string;
    
    // LoadMore properties
    protected loadingMore: boolean = false;
    protected hasMore: boolean = true;
    protected lastItemId: number = 0;
    static componentFactory: IComponentFactory;

    constructor($element: Cash) {
        this.$element = $element;
        this.i = globalI;
        globalI += 1;

        if (this.$element.data('cloudflare-turnstile') === 'on') {
            this.setupCloudflareTurnstile();
        }

        this.$element.on('reload', this.triggerReload.bind(this));
        
        // Setup LoadMore if supported
        if (this.$element.data('supports-load-more')) {
            this.setupLoadMore();
        }
        
        this.observe();
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
            control.on('change', this.reload.bind(this));
            if (control.is('input[type=text], input[type=password], input[type=email]')) {
                control.on('keyup', this.onControlKeyUp.bind(this));
            }
        });
    }

    onControlKeyUp(e: any): void {
        const $input = $(e.currentTarget);
        if (this.reloadTimer) {
            clearTimeout(this.reloadTimer);
        }
        this.reloadTimer = setTimeout(this.reload.bind(this), this.keyUpReloadDelay);
    }

    protected getReloadData(): ReloadData {
        const data: ReloadData = {
            componentIdNum: this.$element.attr('id').substring(4),
        };

        if (this.cloudflareTurnstileToken) {
            data['cloudflareTurnstileToken'] = this.cloudflareTurnstileToken;
        }
        this.controls.forEach(($control: Cash) => {
            let key = $control.data('request-key');
            
            // Skip controls without request-key (like pagination)
            if (!key) {
                return;
            }
            let val = $control.val();
            if (typeof val === 'undefined' || val === '') {
                val = $control.data('value');
            }
            if (typeof val === 'undefined' || val === '') {
                return;
            }
            if ($control.is('input[type=checkbox]')) {
                val = $control.prop('checked');
            }

            if ($control.data('request-unpack-value-from-json') === 'yes') {
                // @ts-ignore
                _.assign(data, JSON.parse(val));
            } else {
                data[key] = val;
            }
        });

        return data;
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
            if (key === 'componentIdNum') {
                continue;
            }
            let value: string;

            if (data[key] instanceof Array) {
                value = (data[key] as string[]).join(',');
            } else {
                value = data[key] as string;
            }
            thisUrl.searchParams.set(key, value);
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
    }

    protected onReloadError(reloadNumber: number, error: AxiosError): void {
        this.setReloading(false);
        // @ts-ignore
        new ErrorToast(error.response.data);
    }

    /**
     * Setup infinite scroll functionality
     */
    protected setupLoadMore(): void {
        // Get the last item ID and hasMore status from existing items
        this.updateLastItemId();
        this.updateHasMoreFromLastItem();

        // Setup scroll listener with throttling
        let scrollTimer: ReturnType<typeof setTimeout> | null = null;
        const scrollHandler = () => {
            if (scrollTimer) return;
            scrollTimer = setTimeout(() => {
                this.checkScrollPosition();
                scrollTimer = null;
            }, 100);
        };

        $(window).on('scroll', scrollHandler);
        
        // Initial check in case content is short
        setTimeout(() => this.checkScrollPosition(), 100);
    }

    /**
     * Update the last item ID from the DOM
     */
    protected updateLastItemId(): void {
        const $items = this.$element.find('[data-items-container] [data-item-id]');
        if ($items.length > 0) {
            const lastItem = $items.last();
            this.lastItemId = parseInt(lastItem.data('item-id') || '0');
        }
    }

    /**
     * Update hasMore status from the last item's data-has-more attribute
     */
    protected updateHasMoreFromLastItem(): void {
        const $items = this.$element.find('[data-items-container] [data-item-id]');
        if ($items.length > 0) {
            const lastItem = $items.last();
            const hasMoreData = lastItem.data('has-more');
            this.hasMore = hasMoreData === 'true' || hasMoreData === true;
        } else {
            this.hasMore = false;
        }
    }

    /**
     * Check if we need to load more content
     */
    protected checkScrollPosition(): void {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
        const windowHeight = window.innerHeight || 0;
        const docHeight = document.documentElement.scrollHeight || 0;

        if (this.loadingMore || !this.hasMore) {
            return;
        }
        
        // Trigger load more when user is 500px from bottom
        if (scrollTop + windowHeight >= docHeight - 500) {
            this.loadMore();
        }
    }

    /**
     * Load more items
     */
    protected loadMore(): void {
        if (this.loadingMore || !this.hasMore) {
            return;
        }

        this.setLoadingMore(true);

        // Wait 1 second before firing the request
        setTimeout(() => {
            const loadMoreData = this.getLoadMoreData();
            
            axios.get(this.$element.data('load-more-url'), {
                params: loadMoreData
            })
            .then(this.onLoadMoreSuccess.bind(this))
            .catch((error) => {
                this.hasMore = false; // Stop trying on error
                this.onLoadMoreError(error);
            });
        }, 1000);
    }

    /**
     * Get data for load more request
     */
    protected getLoadMoreData(): ReloadData {
        const data = this.getReloadData();
        data['reload'] = 1;  // This triggers component-only rendering
        data['more'] = 1;
        data['fromId'] = this.lastItemId;
        return data;
    }

    /**
     * Handle successful load more response
     */
    protected onLoadMoreSuccess(response: AxiosResponse): void {
        this.setLoadingMore(false);

        // Parse the response to extract new items (they're the direct children now)
        const $response = $(response.data);
        const $newItems = $response.filter('[data-item-id]');
        
        if ($newItems.length === 0) {
            this.hasMore = false;
            this.updateStatusContainer();
            return;
        }

        // Append new items to the container
        const $container = this.$element.find('[data-items-container]');
        $container.append($newItems);

        // Update last item ID and hasMore status from the newly appended items
        this.updateLastItemId();
        this.updateHasMoreFromLastItem();

        // Update the status container based on hasMore
        this.updateStatusContainer();

        // Observe new items
        this.observeItems($newItems);

        // Continue checking scroll position
        setTimeout(() => this.checkScrollPosition(), 100);
    }

    /**
     * Handle load more error
     */
    protected onLoadMoreError(error: AxiosError): void {
        this.setLoadingMore(false);
        // @ts-ignore
        new ErrorToast(error.response?.data || 'Failed to load more items');
    }

    /**
     * Set loading more state
     */
    protected setLoadingMore(loading: boolean): void {
        this.loadingMore = loading;
        
        if (loading) {
            // Show loading state, hide no-more state
            this.$element.find('[data-load-more-state="loading"]').show();
            this.$element.find('[data-load-more-state="no-more"]').hide();
        }
    }

    /**
     * Update the status container based on hasMore state
     */
    protected updateStatusContainer(): void {
        if (this.hasMore) {
            // Show loading state, hide no-more state
            this.$element.find('[data-load-more-state="loading"]').show();
            this.$element.find('[data-load-more-state="no-more"]').hide();
        } else {
            // Hide loading state, show no-more state
            this.$element.find('[data-load-more-state="loading"]').hide();
            this.$element.find('[data-load-more-state="no-more"]').show();
        }
    }

    /**
     * Observe new items - override in subclasses
     */
    protected observeItems($items: Cash): void {
        // Default implementation - instantiate any components in new items
        $items.find('.tnc-component').each((i: number, element: Element) => {
            HTMLComponent.componentFactory.createComponent($(element));
        });
    }
}

export default HTMLComponent;
export { ReloadData, ReloadMethod };
