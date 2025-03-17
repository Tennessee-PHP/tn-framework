import {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ListArticles extends HTMLComponent {

    protected updateUrlQueryOnReload: boolean = true;

    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination'),
            this.$element.find('.tn-tn_core-component-user-select-userselect-userselect'),
            this.$element.find('.tn-tn_cms-component-input-select-categoryselect')
        ];

        this.observeControls();
    }

    protected setReloading(reloading: Boolean): void {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        super.setReloading(reloading);
    }
}