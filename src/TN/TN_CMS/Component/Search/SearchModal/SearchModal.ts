import $, {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import {Modal} from "bootstrap";
import _ from "lodash";

export default class SearchModal extends HTMLComponent {
    private modal: Modal;
    private $searchInput: Cash;
    private $editSearchBtn: Cash;
    private $modalFooter: Cash;

    protected observe(): void {
        this.modal = new Modal(this.$element.get(0));
        this.$searchInput = this.$element.find('#search_input');
        this.$element.get(0).addEventListener('shown.bs.modal', () => {
            this.$searchInput.get(0).focus();
            this.$searchInput.trigger('keyup');
        });
        this.$editSearchBtn = this.$element.find('.modal-footer a.btn');
        this.$modalFooter = this.$element.find('.modal-footer');
        if (this.$editSearchBtn.length) {
            this.$searchInput.on('keyup', this.onSearchChange.bind(this));
        }
    }

    protected onSearchChange(): void {
        let search: string = <string>this.$searchInput.val();
        if (search.length < 3) {
            this.$editSearchBtn.addClass('disabled');
            this.$modalFooter.addClass('d-none');
            return;
        }

        this.$editSearchBtn.removeClass('disabled');
        this.$editSearchBtn.attr('href', this.$editSearchBtn.data('base-url') + search);
        this.$modalFooter.removeClass('d-none');
    }

}