import $, {Cash} from 'cash-dom';
import {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import axios, {AxiosError, AxiosResponse} from "axios";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';
import _ from "lodash";

export default class EditUserTabField {
    private $form: Cash;
    private $button: Cash;
    private $inputs: Cash;
    private useFormData: boolean;

    public constructor($form: Cash, useFormData: boolean = false) {
        this.$form = $form;
        this.useFormData = useFormData;
        this.$inputs = this.$form.find('input');
        this.$button = this.$form.find('button');
        this.observe();
    }

    protected observe(): void {
        this.$inputs.on('keypress', this.onFieldKeyPress.bind(this));
        this.$form.on('submit', this.onSubmit.bind(this));
    }

    protected onFieldKeyPress(event: KeyboardEvent): void {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.$button.trigger('click');
        }
    }

    protected onSubmit(event: MouseEvent): void {
        event.preventDefault();
        this.$button.prop('disabled', true);
        this.$button.find('.spinner-border').removeClass('d-none');

        let data: ReloadData = this.$form.getFormData();

        if (this.useFormData) {
            // @ts-ignore
            data = new FormData(this.$form.get(0));
        }
        axios.post(this.$form.attr('action'), data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    if (response.data.logoutRequired) {
                        new SuccessToast('Password updated. You have been logged out. Please sign in with your new password.');
                        const loginUrl = (typeof TN !== 'undefined' && TN.BASE_URL)
                            ? TN.BASE_URL + 'auth/login?redirect_url=' + encodeURIComponent(TN.BASE_URL + 'me/profile')
                            : '/auth/login';
                        _.delay(() => {
                            window.location.href = loginUrl;
                        }, 1500);
                        return;
                    }
                    this.$button.find('.bi-check-circle').removeClass('d-none');
                    this.$button.removeClass('btn-outline-primary').addClass('btn-success');
                    this.$form.trigger('success', response.data);
                    _.delay(() => {
                        this.$button.removeClass('btn-success').addClass('btn-outline-primary');
                        this.$button.find('.bi-check-circle').addClass('d-none');
                    }, 2000);
                }
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response.data.message);
            })
            .finally((): void => {
                this.$button.prop('disabled', false);
                this.$button.find('.spinner-border').addClass('d-none');
            });
    }
}