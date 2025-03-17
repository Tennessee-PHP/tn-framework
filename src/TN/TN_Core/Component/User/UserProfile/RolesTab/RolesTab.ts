import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import $, {Cash} from 'cash-dom';
import axios, {AxiosError, AxiosResponse} from "axios";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class RolesTab extends HTMLComponent {
    private $form: Cash;
    protected observe(): void {
        this.$form = this.$element.find('form');
        this.$form.on('submit', this.onFormSubmit.bind(this));
    }

    private onFormSubmit(event: Event): void {
        event.preventDefault();
        this.setReloading(true);

        axios.post(this.$form.attr('action'), this.$form.getFormData(), {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                }
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response.data.message);
            })
            .finally((): void => {
                this.setReloading(false);
            });
    }

}