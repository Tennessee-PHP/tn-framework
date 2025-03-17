import {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ErrorLog extends HTMLComponent {
    protected updateUrlQueryOnReload: boolean = true;

    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination')
        ];
        this.observeControls();
    }

    protected setReloading(reloading: Boolean): void {
        this.$element.find('.card').get()[0].scrollIntoView();
    }
}