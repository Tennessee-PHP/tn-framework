import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import axios from "axios";
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class ListSearchQueries extends HTMLComponent {
    private sortBy: any;
    private sortOrder: any;
    private $minCountSelect: Cash;
    private minCount: string | number | string[];
    protected updateUrlQueryOnReload: boolean = true;
    
    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination')
        ];
        this.observeControls();
        this.sortBy = this.$element.data('sortby');
        this.sortOrder = this.$element.data('sortorder');

        this.$element.find('th a.sort-by').on('click', this.onSortByColumnClick.bind(this));
        this.$minCountSelect = $('#minTotalCount');
        this.minCount = this.$minCountSelect.val();
        this.$minCountSelect.on('change', this.onMinCountSelectChange.bind(this));
        $('#confirm_clear_queries_btn').on('click', this.confirmClearSearchQueries.bind(this));
    }

    protected confirmClearSearchQueries(): void {
        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/search/queries/clear', {
            confirm: 1
        }, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(() => window.location.reload())
            .catch((response: any) => new ErrorToast(response.responseText));
    }

    protected onMinCountSelectChange(): void {
        this.minCount = this.$minCountSelect.val();
        this.reload();
    }

    protected onSortByColumnClick(e: any): void {
        e.preventDefault();
        let $a = $(e.target);
        $a = $a.is('a') ? $a : $a.parents('a').first();

        let newSortBy = $a.data('sortby');
        if (newSortBy === this.sortBy) {
            this.sortOrder = this.sortOrder === 'DESC' ? 'ASC' : 'DESC';
        } else {
            this.sortOrder = newSortBy === 'selectedRate' ? 'ASC' : 'DESC';
        }
        this.sortBy = newSortBy;
        this.$element.find('.tn-tn_core-component-pagination-pagination').data('value', 1);
        this.reload();
    }

    protected getReloadData(): ReloadData {
        let data: ReloadData = super.getReloadData();
        data.sortby = this.sortBy;
        data.sortorder = this.sortOrder;
        data.mincount = this.minCount;
        return data;
    }
    
}