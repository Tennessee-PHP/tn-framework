import {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ListUsersTable extends HTMLComponent {
    protected updateUrlQueryOnReload: boolean = true;
    private $listUsers: Cash;

    protected observe(): void {
        this.$listUsers = this.$element.parents('.tn-tn_core-component-user-listusers-listusers');
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination'),
            this.$listUsers.find('.tn-tn_core-component-input-select-roleselect-roleselect'),
            this.$listUsers.find('#username_search_field'),
            this.$listUsers.find('#email_search_field')
        ];
        this.observeControls();
    }

    protected setReloading(reloading: Boolean): void {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}