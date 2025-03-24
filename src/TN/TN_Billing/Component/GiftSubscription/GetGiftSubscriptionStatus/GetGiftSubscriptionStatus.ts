import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';
import axios, { AxiosResponse, AxiosError } from 'axios';
import { Cash } from 'cash-dom';

export default class GetGiftSubscriptionStatus extends HTMLComponent {
    // Component elements
    private $form: Cash;
    private $submitButton: Cash;

    protected observe(): void {
        // Initialize element references
        // @ts-ignore
        this.$form = this.$element.find('form');
        this.$submitButton = this.$form.find('[type="submit"]');

        // Set up event listeners
        this.$form.on('submit', (e: Event): void => {
            e.preventDefault();
            this.handleSubmit();
        });
    }

    private handleSubmit(): void {
        // Disable submit button and show spinner
        this.$submitButton.prop('disabled', true);
        this.$submitButton.find('.spinner-border').removeClass('d-none');

        const data = this.$form.getFormData();

        axios.post(this.$form.attr('action'), data)
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
                this.$submitButton.prop('disabled', false);
                this.$submitButton.find('.spinner-border').addClass('d-none');
            });
    }
}
