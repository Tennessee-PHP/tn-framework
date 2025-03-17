import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import $ from 'cash-dom';

export default class Pagination extends HTMLComponent {

    protected observe(): void {
        this.$element.find('.page-link').on('click', this.onPageLinkClick.bind(this));
    }

    protected onPageLinkClick(e: any): void {
        e.preventDefault();
        this.$element.data('value', $(e.currentTarget).data('page'));
        this.$element.trigger('change');
    }
}