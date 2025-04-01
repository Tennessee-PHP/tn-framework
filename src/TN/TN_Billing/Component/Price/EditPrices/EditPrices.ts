import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";
import axios, { AxiosError, AxiosResponse } from "axios";
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
        axios.post(TN.BASE_URL + 'staff/sales/change-prices/submit', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(this.onSaveSuccess.bind(this))
            .catch(this.onSaveError.bind(this));
    }

    onSaveSuccess(response: AxiosResponse) {
        this.onSaveComplete();
        if (response.data.success) {
            new SuccessToast(response.data.message);
        }
    }
    onSaveError(error: AxiosError) {
        this.onSaveComplete();
        // @ts-ignore
        new ErrorToast(error.response.data.message);
    }

    onSaveComplete() {
        console.log($('#change_price_modal').get(0));
        console.log(new Modal($('#change_price_modal').get(0)));
        new Modal($('#change_price_modal').get(0)).hide();
    }
}