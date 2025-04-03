import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData, ReloadMethod} from '@tn/TN_Core/Component/HTMLComponent';

export default class LoginForm extends HTMLComponent {

    protected reloadMethod: ReloadMethod = 'post';

    private action: string;

    protected observe() {
        // if the element is inside a modal, let's focus the login field
        let $modal: Cash = this.$element.parents('.modal');
        if ($modal.length) {
            $modal.get(0).addEventListener('shown.bs.modal', this.focusLoginField.bind(this));
        }
        this.$element.on('submit', this.onSubmit.bind(this));
        this.action = this.$element.data('action');
        if (this.action === 'login' && this.$element.data('login-success')) {
            // wait 2 seconds and then reload the page
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }


        // observe change action buttons
        this.$element.find('a.change-action').on('click', this.onChangeAction.bind(this));

        // observe create account button
        //this.$element.find('a.create-account-button').on('click', this.onCreateAccount.bind(this));
    }

    protected onCreateAccount(e: Event) {
        e.preventDefault();
        const target = e.currentTarget as HTMLElement;
        this.$element.data('reload-url', $(target).data('url'));
        this.reload();
    }

    protected onChangeAction(e: any) {
        e.preventDefault();
        this.action = $(e.currentTarget).data('action');
        this.reload();
    }

    protected focusLoginField() {
        this.$element.find('input[type="text"]').get(0).focus();
    }

    protected getReloadData(): ReloadData {
        let data = super.getReloadData();
        data.login = this.$element.find('input[name="login"]').val();
        if (this.action === 'login') {
            data.password = this.$element.find('input[name="password"]').val();
        }
        data.action = this.action;
        return data;
    }

    protected onSubmit(e: Event): void {
        e.preventDefault();
        this.reload();
    }
}