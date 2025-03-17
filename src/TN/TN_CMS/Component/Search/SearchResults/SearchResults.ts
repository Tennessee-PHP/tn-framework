import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import $, {Cash} from 'cash-dom';
import _ from "lodash";
import axios from "axios";

export default class SearchResults extends HTMLComponent {
    private $searchInput: Cash;
    private search: string = '';
    private onSearchChangeDebounced: any;
    private onSearchChangeBound: any;
    private onMaybeNavigateResultsBound: any;
    private active: boolean;

    protected observe(): void {
        this.$searchInput = $('#search_input');
        this.search = <string>this.$element.data('search').trim();
        this.active = true;
        this.onSearchChangeDebounced = _.debounce(this.onSearchChange.bind(this), 500);
        this.onSearchChangeBound = this.onSearchChange.bind(this);
        this.onMaybeNavigateResultsBound = this.maybeNavigateResults.bind(this);
        this.$searchInput.on('keyup', this.onSearchChangeDebounced);
        this.$searchInput.on('search', this.onSearchChangeBound);
        this.$searchInput.on('keydown', this.onMaybeNavigateResultsBound);
        this.$element.find('a.list-group-item').on('click', this.onResultClick.bind(this));
    }

    protected unobserve(): void {
        this.active = false;
        this.$searchInput.off('keyup', this.onSearchChangeDebounced);
        this.$searchInput.off('search', this.onSearchChangeBound);
        this.$searchInput.off('keydown', this.onMaybeNavigateResultsBound);
    }

    protected maybeNavigateResults(e: any): void {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            let $active = this.$element.find('.list-group-item.active');
            if ($active.length) {
                $active.removeClass('active');
                let $next = e.key === 'ArrowDown' ? $active.next() : $active.prev();
                if ($next.length) {
                    $next.addClass('active');
                } else {
                    this.$element.find('.list-group-item').first().addClass('active');
                }
            } else {
                this.$element.find('.list-group-item').first().addClass('active');
            }
        }
        if (e.key === 'Enter') {
            let $active = this.$element.find('.list-group-item.active');
            if ($active.length) {
                $active.trigger('click');
            }
        }
    }

    protected setReloading(reloading: Boolean): void {
        this.$element.find('.component-loading').toggleClass('d-none', !reloading);
        if (reloading) {
            this.$element.find('.list-group').remove();
        }
    }

    protected onResultClick(event: Event): void {
        // @ts-ignore
        let $a: Cash = $(event.target);
        if (!$a.is('a')) {
            $a = $a.closest('a');
        }
        const pageEntryId: number = _.parseInt($a.data('page-entry-id'));
        axios.post(this.$element.data('result-selected-url'), {
            search: this.search,
            pageEntryId: pageEntryId
        }, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    }

    protected onSearchChange(): void {
        if (!this.active) {
            return;
        }
        let searchInputValue: string = (<string>this.$searchInput.val()).trim();
        if (searchInputValue.length < 3) {
            this.$element.find('.list-group').remove();
            return;
        }
        if (this.$element.data('search') === searchInputValue) {
            return;
        }
        this.search = searchInputValue;
        this.reload();
    }

    protected getReloadData(): ReloadData {
        let data: ReloadData = super.getReloadData();
        data.search = this.search;
        return data;
    }
}