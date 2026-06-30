import {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';

export default class ListCancellationAttempts extends HTMLComponent {
    protected updateUrlQueryOnReload: boolean = true;
    private $filterForm: Cash;

    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination'),
        ];
        this.observeControls();

        this.$filterForm = this.$element.find('form.filter-form');
        this.$filterForm.on('submit', this.onFilterFormSubmit.bind(this));
    }

    protected onFilterFormSubmit(e: Event): void {
        e.preventDefault();
        this.reload();
    }

    protected getReloadData(): ReloadData {
        const data: ReloadData = super.getReloadData();
        return {...data, ...this.$filterForm.getFormData()};
    }
}
