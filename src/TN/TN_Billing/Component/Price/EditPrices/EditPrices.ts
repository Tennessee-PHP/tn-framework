import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";
import axios from "axios";
import SuccessToast from "@tn/TN_Core/Component/Toast/SuccessToast";
import ErrorToast from "@tn/TN_Core/Component/Toast/ErrorToast";
import {Modal} from "bootstrap";

export default class EditPrices extends HTMLComponent {
    private $form: Cash;
    
    protected observe(): void {
        this.$form = this.$element.find('#change_prices_form');
        this.$form.on('submit', this.onFormSubmit.bind(this));
    }

    onFormSubmit(e: any) {
        e.preventDefault();
        let data: ReloadData = this.$form.getFormData();

        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/sales/change-prices/submit', {
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
            $('#advert_id_field').val(response.data.advertId);
            new SuccessToast(response.data.message);
        }
    }
    onSaveError(response: any) {
        this.onSaveComplete();
        new ErrorToast(response.data.message);
    }

    onSaveComplete() {
        new Modal(document.getElementById('change_price_modal')).hide();
    }
}