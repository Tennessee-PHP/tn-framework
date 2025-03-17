import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData, ReloadMethod} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";
import axios, {AxiosError, AxiosResponse} from "axios";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class RegisterForm extends HTMLComponent {

    protected reloadMethod: ReloadMethod = 'post';
    
    protected observe(): void {
        if (this.$element.data('success') == '1') {
            setTimeout(() => {
                window.location.href = this.$element.data('redirect-url');
            }, 500);
        }
        this.$element.on('submit', this.onSubmit.bind(this));
        this.$element.find('#field_email').on('blur', this.onEmailBlur.bind(this));
    }

    protected onEmailBlur(): void {

        if (this.$element.find('#field_username').val() !== '') {
            return;
        }
        let data: ReloadData = { email: this.$element.find('#field_email').val() };

        // @ts-ignore
        axios.get(TN.BASE_URL + 'register/suggest-username', { params: data })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    this.$element.find('#field_username').val(response.data.username);
                }
            });
    }

    protected onSubmit(e: any): void {
        e.preventDefault();
        this.reload();
    }

    protected getReloadData(): any {
        let data = super.getReloadData();
        data = _.assign(data, this.$element.getFormData());
        data.redirect_url = this.$element.data('redirect-url');
        return data;
    }
    
}