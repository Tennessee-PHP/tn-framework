import {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ListRefunds extends HTMLComponent {
    private $yearSelect: Cash;
    private $reasonSelect: Cash;

    
    protected observe(): void {
        this.$yearSelect = this.$element.find('#year_select');
        this.$yearSelect.on('change', this.onYearSelectChange.bind(this));
        this.$reasonSelect = this.$element.find('#reason_select');
        this.$reasonSelect.on('change', this.onReasonSelectChange.bind(this));
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination'),
            this.$yearSelect,
            this.$reasonSelect
        ]
        this.observeControls();
    }

    protected setReloading(reloading: boolean): void {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    protected onYearSelectChange(): void {
        this.$yearSelect.data('value', this.$yearSelect.val());
    }

    protected onReasonSelectChange(): void {
        this.$reasonSelect.data('value', this.$reasonSelect.val());
    }
    
}