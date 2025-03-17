import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";
import axios from "axios";
import SuccessToast from "@tn/TN_Core/Component/Toast/SuccessToast";
import ErrorToast from "@tn/TN_Core/Component/Toast/ErrorToast";

export default class ListGiftSubscriptions extends HTMLComponent {
    private $form: Cash;
    private $textarea: Cash;

    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination')
        ];
        this.observeControls();
        this.$form = this.$element.find('#staff_comp_form');
        this.$textarea = this.$form.find('#email_input_field');
        this.$form.on('submit', this.onFormSubmit.bind(this));
        this.$element.find('#duration_select').on('change', this.onLifetimeSub.bind(this));
    }

    protected setReloading(): void {
        window.scrollTo(0, 0);
    }

    onLifetimeSub(e: any) {
        const target = this.$element.find('#duration_select').val();
        const billing = this.$element.find('#billing_select');

        // @ts-ignore
        if (parseInt(target) === 100) {
            billing.val('annually');
            billing.prop('disabled', true);
        } else {
            billing.prop('disabled', false);
        }
    }

    onFormSubmit(e: any) {
        e.preventDefault();
        let data: ReloadData = this.$form.getFormData();

        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/sales/submit', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(this.onSubmitSuccess.bind(this))
            .catch(this.onSubmitError.bind(this));
    }

    onSubmitSuccess(response: any): void {
        if (response.data.result === 'success') {
            $('#advert_id_field').val(response.data.advertId);
            new SuccessToast(response.data.message);
        }
        this.reload();
    }

    onSubmitError(response: any): void {
        new ErrorToast(response.data.message);
    }

}