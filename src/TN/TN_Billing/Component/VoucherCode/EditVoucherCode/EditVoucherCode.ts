import {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import axios from "axios";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from "@tn/TN_Core/Component/Toast/ErrorToast";

export default class EditVoucherCode extends HTMLComponent {
    private $form: Cash;
    private $submitBtn: Cash;
    private $loading: Cash;

    protected observe(): void {
        this.$form = this.$element.find('#edit_vouchercode');
        this.$submitBtn = this.$form.find('input.btn-primary');
        this.$loading = this.$form.find('#edit-loading');
        this.$form.on('submit', this.onFormSubmit.bind(this));
    }

    onRedirectToHome() {
        this.$submitBtn.prop('disabled', true);
        // @ts-ignore
        window.location.href = `${TN.BASE_URL}staff/sales/voucher-codes/list`;
    }

    onFormSubmit(e: any) {
        e.preventDefault();
        let data: ReloadData = this.$form.getFormData();
        this.$submitBtn.hide();
        this.$loading.show();
        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/sales/voucher-codes/save', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(this.onSaveSuccess.bind(this))
            .catch(this.onSaveError.bind(this));
    }

    onSaveSuccess(response: any) {
        this.onSaveComplete();
        if (response.data.result === 'success') {
            new SuccessToast(response.data.message);
        }
        setTimeout(this.onRedirectToHome.bind(this), 2000);
    }

    onSaveError(response: any) {
        this.onSaveComplete();
        new ErrorToast(response.data.message);
    }

    onSaveComplete() {
        this.$submitBtn.show();
        this.$loading.hide();
    }

}