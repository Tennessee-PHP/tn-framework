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
    protected observe(): void {
        this.$cancelForm = this.$element.find('#user_plans_staffer_cancel_form');
        this.$cancelForm.on('submit', this.onCancelFormSubmit.bind(this));
        this.$refundForm = this.$element.find('#user_plans_staffer_refunds_form');
        this.$refundForm.on('submit', this.onRefundFormSubmit.bind(this));
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
                    new Modal(document.getElementById('cancelplan_modal')).hide();
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
        let data: ReloadData = this.$refundForm.getFormData();
        data.cancel = 0;
        $('.refund-check').each((i, box) => {
            let $box = $(box);
            if ($box.is(':checked')) {
                data[$box.attr('name')] = $box.val();
            }
        });

        axios.post(this.$refundForm.attr('action'), data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
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

}