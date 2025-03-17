import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";
import axios from "axios";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class EditEmailTemplate extends HTMLComponent {

    public $form: Cash;
    public $submitBtn: Cash;
    public $loading: Cash;
    
    protected observe(): void {
        this.$form = this.$element.find('#form_edit_email_template');
        this.$form.on('submit', this.onFormSubmit.bind(this));
        this.$submitBtn = this.$form.find('#email_tpl_preview_btn');
        this.$loading = this.$form.find('#email_template_loading');

        // @ts-ignore
        tinymce.init({
            selector: 'textarea#editor',
            skin: 'bootstrap',
            content_css: [
                // @ts-ignore
                TN.CSS_URL,
                // @ts-ignore
                TN.FONTS_CSS_URL
            ],
            relative_urls: false,
            remove_script_host: false,
            automatic_uploads: true,
            toolbar_sticky: true,
            toolbar_sticky_offset: 50,
            plugins: 'lists, link, media, autoresize, tinymcespellchecker, preview',
            toolbar: [
                'bold italic link | bullist numlist '
            ],
            menubar: false,
            // @ts-ignore
            key: TN.TINYMCE_BOOTSTRAP_KEY
        });
    }

    protected onFormSubmit(e: Event): void {
        e.preventDefault();
        let data: ReloadData = this.$form.getFormData();
        this.$submitBtn.hide();
        this.$loading.show();

        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/emails/save', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(this.onSaveSuccess.bind(this))
            .catch(this.onSaveError.bind(this));
    }

    onSaveComplete(): void {
        this.$submitBtn.show();
        this.$loading.hide();
    }

    onSaveSuccess(response: any): void {
        this.onSaveComplete();
        if (response.data.result === 'success') {
            $('#advert_id_field').val(response.data.advertId);
            new SuccessToast(response.data.message);
        }
    }

    onSaveError(response: any): void {
        this.onSaveComplete();
        new ErrorToast(response.data.message);
    }
    
}