import HTMLComponent, {ReloadData} from "@tn/TN_Core/Component/HTMLComponent";
import $, {Cash} from "cash-dom";
import axios, {AxiosError, AxiosResponse} from "axios";
import SuccessToast from "@tn/TN_Core/Component/Toast/SuccessToast";
import ErrorToast from "@tn/TN_Core/Component/Toast/ErrorToast";
import {Modal} from "bootstrap";
import _ from "lodash";

export default class BillingTab extends HTMLComponent {
    private $cancelForm: Cash;
    private $refundForm: Cash;
    private $refundCheckboxes: Cash;
    private $refundButtons: Cash;

    protected observe(): void {
        this.$cancelForm = this.$element.find('#user_plans_staffer_cancel_form');
        this.$refundForm = this.$element.find('#user_plans_staffer_refunds_form');
        this.$refundCheckboxes = this.$element.find('.refund-check');
        this.$refundButtons = this.$element.find('.refund-btn');

        this.$cancelForm.on('submit', this.onCancelFormSubmit.bind(this));
        this.$refundForm.on('submit', this.onRefundFormSubmit.bind(this));
        this.$refundCheckboxes.on('change', this.updateRefundButtonState.bind(this));

        // Set initial state
        this.updateRefundButtonState();
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
        axios.post(this.$cancelForm.attr('action'), data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                    // @ts-ignore: Modal typing might be incomplete or instance needed
                    const cancelModalInstance = Modal.getInstance(document.getElementById('cancelplan_modal')) || new Modal(document.getElementById('cancelplan_modal'));
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

        axios.post(this.$refundForm.attr('action'), payload, { // Send the structured payload
            headers: {
                // Adjust content type if necessary, though form data might still work
                // depending on server config, or switch to 'application/json'
                 'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                     // @ts-ignore: Modal typing might be incomplete or instance needed
                    const refundModalInstance = Modal.getInstance(document.getElementById('actionrefund_modal')) || new Modal(document.getElementById('actionrefund_modal'));
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

}