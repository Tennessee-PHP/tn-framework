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
    private $scheduleDowngradeForm: Cash;
    private $cancelScheduledDowngradeForm: Cash;
    private $scheduleDowngradeRadios: Cash;
    private $scheduleDowngradeSubmit: Cash;
    private $scheduleDowngradeConfirmCopy: Cash;
    private $resumeAutoRenewForm: Cash;
    private $subscriptionVoucherForm: Cash;
    private $nextRenewalComplimentaryForm: Cash;
    private $refundForm: Cash;
    private $refundCheckboxes: Cash;
    private $refundButtons: Cash;

    private $cancelModal: Cash;
    private $cancelReasonSelect: Cash;
    private $cancelComment: Cash;
    private $cancelSurveyContinueBtn: Cash;
    private $cancelAcceptOfferBtn: Cash;
    private $cancelDeclineOfferBtn: Cash;
    private $cancelWizardLoading: Cash;
    private $cancelWizardSteps: Cash;
    private cancellationAttemptId: number = 0;
    private cancellationWizardComplete: boolean = false;

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
            return;
        }
        BillingTab.isInitialized = true;

        // Original form handling
        this.$scheduleDowngradeForm = $('#user_plans_schedule_downgrade_form');
        this.$cancelScheduledDowngradeForm = $('#user_plans_cancel_scheduled_downgrade_form');
        this.$scheduleDowngradeRadios = this.$scheduleDowngradeForm.find('input[name="toPlanKey"]');
        this.$scheduleDowngradeSubmit = $('#schedule_downgrade_submit_btn');
        this.$scheduleDowngradeConfirmCopy = $('#schedule_downgrade_confirm_copy');
        this.$resumeAutoRenewForm = $('#user_plans_resume_autorenew_form');
        this.$subscriptionVoucherForm = $('#user_plans_subscription_voucher_form');
        this.$nextRenewalComplimentaryForm = $('#user_plans_next_renewal_complimentary_form');
        this.$refundForm = $('#user_plans_staffer_refunds_form');
        this.$refundCheckboxes = $('.refund-check');
        this.$refundButtons = $('.refund-btn');


        if (this.$scheduleDowngradeForm.length) {
            this.$scheduleDowngradeForm.on('submit', this.onScheduleDowngradeFormSubmit.bind(this));
            this.$scheduleDowngradeRadios.on('change', this.updateScheduleDowngradeFormState.bind(this));
            this.updateScheduleDowngradeFormState();
        }
        if (this.$cancelScheduledDowngradeForm.length) {
            this.$cancelScheduledDowngradeForm.on('submit', this.onCancelScheduledDowngradeFormSubmit.bind(this));
        }
        if (this.$resumeAutoRenewForm.length) {
            this.$resumeAutoRenewForm.on('submit', this.onResumeAutoRenewFormSubmit.bind(this));
        }
        if (this.$subscriptionVoucherForm.length) {
            this.$subscriptionVoucherForm.on('submit', this.onSubscriptionVoucherFormSubmit.bind(this));
        }
        if (this.$nextRenewalComplimentaryForm.length) {
            this.$nextRenewalComplimentaryForm.on('submit', this.onNextRenewalComplimentaryFormSubmit.bind(this));
        }
        this.$refundForm.on('submit', this.onRefundFormSubmit.bind(this));
        this.$refundCheckboxes.on('change', this.updateRefundButtonState.bind(this));

        this.initCancellationWizard();

        // Set initial state for refund buttons
        this.updateRefundButtonState();

        // Initialize payment form elements
        this.$paymentForm = $('#payment-form');
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

    private updateScheduleDowngradeFormState(): void {
        this.$scheduleDowngradeForm.find('.schedule-downgrade-plan-card').removeClass('form-check-box-checked');

        const $selected = this.$scheduleDowngradeRadios.filter(':checked');
        const planKey = ($selected.val() as string) || '';
        const price = parseFloat(($selected.attr('data-price') as string) || '0');
        const planName = ($selected.attr('data-plan-name') as string) || '';

        if (!planKey) {
            this.$scheduleDowngradeConfirmCopy.text('');
            this.$scheduleDowngradeSubmit.prop('disabled', true);
            return;
        }

        $selected.closest('.schedule-downgrade-plan-card').addClass('form-check-box-checked');

        this.$scheduleDowngradeConfirmCopy.text(
            `On your next renewal you will be charged $${price.toFixed(2)} for the ${planName} plan.`
        );
        this.$scheduleDowngradeSubmit.prop('disabled', false);
    }

    private cancellationCurrentStep: 'survey' | 'save' = 'survey';

    private initCancellationWizard(): void {
        this.$cancelModal = $('#cancelplan_modal');
        if (!this.$cancelModal.length) {
            return;
        }

        this.$cancelReasonSelect = $('#cancellation_reason_select');
        this.$cancelComment = $('#cancellation_comment');
        this.$cancelSurveyContinueBtn = $('#cancellation_survey_continue_btn');
        this.$cancelAcceptOfferBtn = $('#cancellation_accept_offer_btn');
        this.$cancelDeclineOfferBtn = $('#cancellation_decline_offer_btn');
        this.$cancelWizardLoading = this.$cancelModal.find('.cancellation-wizard-loading');
        this.$cancelWizardSteps = this.$cancelModal.find('.cancellation-wizard-step');

        this.$cancelReasonSelect.on('change', this.onCancellationReasonChange.bind(this));
        this.$cancelSurveyContinueBtn.on('click', this.onCancellationSurveyContinue.bind(this));
        this.$cancelAcceptOfferBtn.on('click', this.onCancellationAcceptOffer.bind(this));
        this.$cancelDeclineOfferBtn.on('click', this.onCancellationDeclineOffer.bind(this));

        const modalEl = this.$cancelModal.get(0) as HTMLElement;
        modalEl.addEventListener('show.bs.modal', this.resetCancellationWizard.bind(this));
        modalEl.addEventListener('hidden.bs.modal', this.onCancellationModalHidden.bind(this));
    }

    private resetCancellationWizard(): void {
        this.cancellationAttemptId = 0;
        this.cancellationWizardComplete = false;
        this.cancellationCurrentStep = 'survey';
        this.$cancelReasonSelect.val('');
        this.$cancelReasonSelect.removeClass('is-invalid');
        this.$cancelComment.val('');
        $('#cancellation_other_hint').hide();
        $('#cancellation_offer_available').show();
        $('#cancellation_existing_discount').hide();
        $('#cancellation_fallback_links').hide();
        this.$cancelAcceptOfferBtn.show();
        this.$cancelDeclineOfferBtn.text('No thanks, continue cancellation');
        this.showCancellationStep('survey');
        this.setCancellationWizardLoading(false);
    }

    private formatOfferUsd(amount: number): string {
        return '$' + Number(amount).toFixed(2);
    }

    private fillCancellationOffer(data: {
        offerAmount?: number;
        offerRegularAmount?: number;
        offerDiscountPercentage?: number;
        switchToAnnual?: boolean;
    }): void {
        const pct = Number(data.offerDiscountPercentage || 0);
        const regular = Number(data.offerRegularAmount || 0);
        const discounted = Number(data.offerAmount || 0);
        const stayLine = data.switchToAnnual
            ? `Stay with us for another year on annual billing and we'll take ${pct}% off your first annual renewal.`
            : `Stay with us for another year and we'll take ${pct}% off your next renewal.`;

        $('#cancellation_offer_stay_line').text(stayLine);
        $('.cancellation-offer-pct').text(String(pct));
        $('#cancellation_offer_regular_price').text(this.formatOfferUsd(regular));
        $('#cancellation_offer_discounted_price').text(this.formatOfferUsd(discounted));
        const prefix =
            (this.$cancelAcceptOfferBtn.data('accept-label-prefix') as string) ||
            'Yes, Keep My Access for ';
        this.$cancelAcceptOfferBtn.text(`${prefix}${this.formatOfferUsd(discounted)}`);
    }

    private onCancellationReasonChange(): void {
        const reason = (this.$cancelReasonSelect.val() as string) || '';
        $('#cancellation_other_hint').toggle(reason === 'other');
    }

    private showCancellationStep(step: 'survey' | 'save'): void {
        this.cancellationCurrentStep = step;
        this.$cancelWizardSteps.each((_, el) => {
            const $step = $(el);
            $step.toggle($step.attr('data-step') === step);
        });

        const titles: Record<string, string> = {
            survey: 'Turn Off Auto-Renew',
            save: "We'd love to keep you.",
        };
        $('#cancelPlanModalTitle').text(titles[step] || 'Turn Off Auto-Renew');
    }

    private setCancellationWizardLoading(loading: boolean): void {
        this.$cancelWizardLoading.toggle(loading);
        if (loading) {
            this.$cancelWizardSteps.hide();
        } else {
            this.showCancellationStep(this.cancellationCurrentStep);
        }
    }

    private onCancellationSurveyContinue(): void {
        const reasonCode = (this.$cancelReasonSelect.val() as string) || '';
        if (!reasonCode) {
            this.$cancelReasonSelect.addClass('is-invalid');
            return;
        }
        this.$cancelReasonSelect.removeClass('is-invalid');

        const data = {
            id: this.$cancelModal.data('user-id'),
            reasonCode,
            comment: (this.$cancelComment.val() as string) || '',
        };

        this.setCancellationWizardLoading(true);
        axios
            .post(this.$cancelModal.data('survey-url'), data, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result !== 'success') {
                    return;
                }

                this.cancellationAttemptId = response.data.attemptId;

                const skipSave = !!response.data.skipSaveStep;
                if (skipSave) {
                    $('#cancellation_offer_available').hide();
                    $('#cancellation_fallback_links').show();
                    this.$cancelAcceptOfferBtn.hide();
                    this.$cancelDeclineOfferBtn.text('Turn Off Auto-Renew');

                    const existingLabel = response.data.existingDiscountLabel || '';
                    if (existingLabel) {
                        $('#cancellation_existing_discount_label').text(existingLabel);
                        $('#cancellation_existing_discount').show();
                    } else {
                        $('#cancellation_existing_discount').hide();
                    }
                } else {
                    $('#cancellation_offer_available').show();
                    $('#cancellation_fallback_links').hide();
                    $('#cancellation_existing_discount').hide();
                    this.fillCancellationOffer(response.data);
                    this.$cancelAcceptOfferBtn.show();
                    this.$cancelDeclineOfferBtn.text('No thanks, continue cancellation');
                }

                this.showCancellationStep('save');
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response?.data?.message || 'Could not save your feedback. Please try again.');
            })
            .finally((): void => {
                this.setCancellationWizardLoading(false);
            });
    }

    private onCancellationAcceptOffer(): void {
        if (!this.cancellationAttemptId) {
            return;
        }

        const data = {
            id: this.$cancelModal.data('user-id'),
            attemptId: this.cancellationAttemptId,
        };

        this.setCancellationWizardLoading(true);
        axios
            .post(this.$cancelModal.data('accept-offer-url'), data, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result !== 'success') {
                    return;
                }

                this.cancellationWizardComplete = true;
                new SuccessToast(response.data.message);

                const modalInstance =
                    Modal.getInstance(document.getElementById('cancelplan_modal')) ||
                    new Modal(document.getElementById('cancelplan_modal'));
                modalInstance.hide();

                if (response.data.redirectUrl) {
                    window.location.href = response.data.redirectUrl;
                    return;
                }

                _.delay(() => {
                    window.location.reload();
                }, 1500);
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response?.data?.message || 'Could not apply the offer. Please try again.');
            })
            .finally((): void => {
                this.setCancellationWizardLoading(false);
            });
    }

    private onCancellationDeclineOffer(): void {
        this.completeCancellation();
    }

    private completeCancellation(): void {
        if (!this.cancellationAttemptId) {
            new ErrorToast('Please complete the survey first.');
            this.showCancellationStep('survey');
            return;
        }

        const data = {
            id: this.$cancelModal.data('user-id'),
            attemptId: this.cancellationAttemptId,
        };

        this.setCancellationWizardLoading(true);
        axios
            .post(this.$cancelModal.data('cancel-url'), data, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result !== 'success') {
                    return;
                }

                this.cancellationWizardComplete = true;
                new SuccessToast(response.data.message);

                const modalInstance =
                    Modal.getInstance(document.getElementById('cancelplan_modal')) ||
                    new Modal(document.getElementById('cancelplan_modal'));
                modalInstance.hide();

                _.delay(() => {
                    window.location.reload();
                }, 2000);
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response?.data?.message || 'Could not cancel your subscription. Please try again.');
            })
            .finally((): void => {
                this.setCancellationWizardLoading(false);
            });
    }

    private onCancellationModalHidden(): void {
        if (this.cancellationWizardComplete || !this.cancellationAttemptId) {
            return;
        }

        axios.post(
            this.$cancelModal.data('abandon-url'),
            {
                id: this.$cancelModal.data('user-id'),
                attemptId: this.cancellationAttemptId,
            },
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
    }

    private onScheduleDowngradeFormSubmit(event: Event): void {
        event.preventDefault();
        const data: ReloadData = this.$scheduleDowngradeForm.getFormData();
        axios
            .post(this.$scheduleDowngradeForm.attr('action'), data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                    const modalEl = document.getElementById('scheduledowngrade_modal');
                    if (modalEl) {
                        const modalInstance = Modal.getInstance(modalEl) || new Modal(modalEl);
                        modalInstance.hide();
                    }
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

    private onCancelScheduledDowngradeFormSubmit(event: Event): void {
        event.preventDefault();
        const data: ReloadData = this.$cancelScheduledDowngradeForm.getFormData();
        axios
            .post(this.$cancelScheduledDowngradeForm.attr('action'), data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                    const modalEl = document.getElementById('cancelscheduleddowngrade_modal');
                    if (modalEl) {
                        const modalInstance = Modal.getInstance(modalEl) || new Modal(modalEl);
                        modalInstance.hide();
                    }
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

    private onSubscriptionVoucherFormSubmit(event: Event): void {
        event.preventDefault();
        const data: ReloadData = this.$subscriptionVoucherForm.getFormData();
        axios
            .post(this.$subscriptionVoucherForm.attr('action'), data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
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

    private onNextRenewalComplimentaryFormSubmit(event: Event): void {
        event.preventDefault();
        const data: ReloadData = this.$nextRenewalComplimentaryForm.getFormData();
        axios
            .post(this.$nextRenewalComplimentaryForm.attr('action'), data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                    const modalEl = document.getElementById('makenextrenewalfree_modal');
                    if (modalEl) {
                        const modalInstance = Modal.getInstance(modalEl) || new Modal(modalEl);
                        modalInstance.hide();
                    }
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

    private onResumeAutoRenewFormSubmit(event: Event): void {
        event.preventDefault();
        let data: ReloadData = this.$resumeAutoRenewForm.getFormData();
        axios
            .post(this.$resumeAutoRenewForm.attr('action'), data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                    const resumeModalEl = document.getElementById('resumeautorenew_modal');
                    if (resumeModalEl) {
                        const resumeModalInstance =
                            Modal.getInstance(resumeModalEl) || new Modal(resumeModalEl);
                        resumeModalInstance.hide();
                    }
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
        // Wait for TN.braintreeClientToken to be available
        if (!TN.braintreeClientToken) {
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
                this.$paymentForm.data('update-payment-url'),
                data,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                }
            );

            if (response.data.success) {
                new SuccessToast('Payment method updated successfully');
                window.location.reload();
            } else {
                this.handlePaymentError(response.data.message || response.data.error || 'An error occurred while updating your payment method');
            }
        } catch (error) {
            // @ts-ignore
            this.handlePaymentError(
                error.response?.data?.message || error.response?.data?.error || error.message || 'An error occurred while processing your payment'
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
