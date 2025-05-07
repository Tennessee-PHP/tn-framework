import HTMLComponent, { ReloadData } from '@tn/TN_Core/Component/HTMLComponent';
import $, { Cash } from 'cash-dom';
import axios, { AxiosError, AxiosResponse } from 'axios';
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';
import { Modal } from 'bootstrap';
import _ from 'lodash';
import * as braintree from 'braintree-web';

// @ts-ignore - Global TN variable from framework
declare const TN: any;

export default class BillingTab extends HTMLComponent {
    private $cancelForm: Cash;
    private $refundForm: Cash;
    private $refundCheckboxes: Cash;
    private $refundButtons: Cash;

    // Braintree related properties
    private braintreeClient: braintree.Client;
    private braintreeDeviceData: string;
    private hostedFields: braintree.HostedFields;
    private $paymentForm: Cash;
    private $paymentSubmitButton: Cash;
    private $paymentLoadingIndicator: Cash;
    private static isInitialized: boolean = false;

    protected observe(): void {
        // Prevent multiple instances from initializing
        if (BillingTab.isInitialized) {
            console.log('BillingTab already initialized');
            return;
        }
        BillingTab.isInitialized = true;
        console.log('Initializing BillingTab');

        // Original form handling
        this.$cancelForm = this.$element.find('#user_plans_staffer_cancel_form');
        this.$refundForm = this.$element.find('#user_plans_staffer_refunds_form');
        this.$refundCheckboxes = this.$element.find('.refund-check');
        this.$refundButtons = this.$element.find('.refund-btn');

        this.$cancelForm.on('submit', this.onCancelFormSubmit.bind(this));
        this.$refundForm.on('submit', this.onRefundFormSubmit.bind(this));
        this.$refundCheckboxes.on('change', this.updateRefundButtonState.bind(this));

        // Set initial state for refund buttons
        this.updateRefundButtonState();

        // Initialize payment form elements
        this.$paymentForm = this.$element.find('#payment-form');
        this.$paymentSubmitButton = this.$paymentForm.find('input[type="submit"]');
        this.$paymentLoadingIndicator = this.$element.find('.loading');

        // Initialize Braintree if payment form exists
        if (this.$paymentForm.length) {
            this.initBraintree();
            
            this.$paymentForm.on('submit', (e: Event) => {
                e.preventDefault();
                this.submitPayment();
            });
        }
    }

    private updateRefundButtonState(): void {
        let anyChecked = false;
        this.$refundCheckboxes.each((i, el) => {
            if ($(el).is(':checked')) {
                anyChecked = true;
                return false; // Break the loop early
            }
        });
        this.$refundButtons.prop('disabled', !anyChecked);
    }

    private onCancelFormSubmit(event: Event): void {
        event.preventDefault();
        let data: ReloadData = this.$cancelForm.getFormData();
        axios
            .post(this.$cancelForm.attr('action'), data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                    // @ts-ignore: Modal typing might be incomplete or instance needed
                    const cancelModalInstance =
                        Modal.getInstance(document.getElementById('cancelplan_modal')) ||
                        new Modal(document.getElementById('cancelplan_modal'));
                    cancelModalInstance.hide();
                    _.delay(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response.data.message);
            });
    }

    private onRefundFormSubmit(event: Event): void {
        event.preventDefault();
        // Get base form data (reason, comment)
        let baseData: ReloadData = this.$refundForm.getFormData();

        // Prepare payload object
        let payload: { [key: string]: any } = { ...baseData }; // Start with reason, comment

        // Collect checked transaction IDs
        payload.transactionIds = [];
        this.$refundCheckboxes.each((i, box) => {
            const $box = $(box);
            if ($box.is(':checked')) {
                // @ts-ignore - $box.val() might be string or number, push ensures it's added
                payload.transactionIds.push($box.val());
            }
        });

        // Add cancel flag
        payload.cancel = this.$refundForm.find('#cancel_subscription').is(':checked') ? 1 : 0;

        axios
            .post(this.$refundForm.attr('action'), payload, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                    // @ts-ignore: Modal typing might be incomplete or instance needed
                    const refundModalInstance =
                        Modal.getInstance(document.getElementById('actionrefund_modal')) ||
                        new Modal(document.getElementById('actionrefund_modal'));
                    refundModalInstance.hide();
                    _.delay(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response.data.message);
            });
    }

    // Braintree Integration Methods
    private initBraintree(): void {
        console.log('init braintree');
        // Wait for TN.braintreeClientToken to be available
        if (!TN.braintreeClientToken) {
            console.error('Braintree client token not available');
            this.handlePaymentError('Payment system not properly initialized. Please try again later.');
            return;
        }

        braintree.client
            .create({
                authorization: TN.braintreeClientToken,
            })
            .then(client => {
                this.braintreeClient = client;
                return this.createBraintreeDataCollector()
                    .then(() => this.createBraintreeHostedFields())
                    .then(() => {
                        this.enablePaymentSubmitButton();
                    });
            })
            .catch((error: Error) => {
                this.handlePaymentError('Failed to initialize payment system: ' + error.message);
                console.error(error);
            });
    }

    private createBraintreeDataCollector(): Promise<void> {
        return braintree.dataCollector
            .create({
                client: this.braintreeClient,
            })
            .then(dataCollector => {
                this.braintreeDeviceData = dataCollector.deviceData;
            })
            .catch((err: Error) => {
                return Promise.reject('Error while setting up device data');
            });
    }

    private createBraintreeHostedFields(): Promise<void> {
        return braintree.hostedFields
            .create({
                client: this.braintreeClient,
                styles: {
                    input: {
                        'font-size': '16px',
                        'font-family':
                            '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        color: 'var(--bs-body-color)',
                        'font-weight': '400',
                        'line-height': '1.5',
                    },
                    'input.invalid': {
                        color: 'var(--bs-danger)',
                    },
                    '.valid': {
                        color: 'var(--bs-success)',
                    },
                    ':focus': {
                        color: 'var(--bs-body-color)',
                    },
                },
                fields: {
                    cardholderName: {
                        container: '#cardholder_name',
                        placeholder: 'Name as shown on card',
                    },
                    number: {
                        container: '#card_number',
                        placeholder: '4111 1111 1111 1111',
                    },
                    expirationDate: {
                        container: '#expiration_date',
                        placeholder: 'MM/YY',
                    },
                    cvv: {
                        container: '#cvv',
                        placeholder: '123',
                    },
                },
            })
            .then(hostedFieldsInstance => {
                this.hostedFields = hostedFieldsInstance;

                hostedFieldsInstance.on('validityChange', event => {
                    const field = event.fields[event.emittedBy];
                    const $container = $(`#${event.emittedBy}`);

                    if (field.isValid) {
                        $container.removeClass('is-invalid').addClass('is-valid');
                    } else if (field.isPotentiallyValid) {
                        $container.removeClass('is-invalid is-valid');
                    } else {
                        $container.removeClass('is-valid').addClass('is-invalid');
                    }
                });
            })
            .catch((err: Error) => {
                return Promise.reject(err);
            });
    }

    private async submitPayment(): Promise<void> {
        this.showPaymentLoading();
        this.disablePaymentSubmitButton();

        try {
            const { nonce } = await this.hostedFields.tokenize();

            const data = {
                nonce,
                devicedata: this.braintreeDeviceData,
                processpayment: this.$paymentForm.find('input[name="processpayment"]').val(),
            };

            const response = await axios.post(
                this.$element.find('#payment-form').closest('[data-update-payment-url]').data('update-payment-url'),
                data
            );

            if (response.data.success) {
                new SuccessToast('Payment method updated successfully');
                window.location.reload();
            } else {
                this.handlePaymentError(response.data.error || 'An error occurred while updating your payment method');
            }
        } catch (error) {
            // @ts-ignore
            this.handlePaymentError(
                error.response?.data?.error || error.message || 'An error occurred while processing your payment'
            );
        } finally {
            this.hidePaymentLoading();
            this.enablePaymentSubmitButton();
        }
    }

    private handlePaymentError(message: string): void {
        new ErrorToast(message);
        this.$element.find('.alert-danger').text(message).show();
    }

    private showPaymentLoading(): void {
        this.$paymentLoadingIndicator.show();
    }

    private hidePaymentLoading(): void {
        this.$paymentLoadingIndicator.hide();
    }

    private enablePaymentSubmitButton(): void {
        this.$paymentSubmitButton.prop('disabled', false);
    }

    private disablePaymentSubmitButton(): void {
        this.$paymentSubmitButton.prop('disabled', true);
    }
}
