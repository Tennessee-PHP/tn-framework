import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";
import axios, {AxiosError, AxiosResponse} from 'axios';
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class EditCampaign extends HTMLComponent {
    private $form: Cash;
    private $submitBtn: Cash;
    private $loading: Cash;

    protected observe(): void {
        this.$form = this.$element.find('#edit_campaign');
        this.$submitBtn = this.$form.find('input.btn-primary');
        this.$loading = this.$form.find('#edit-loading');
        this.initTinyMce();
        this.$form.on('submit', this.onFormSubmit.bind(this));
    }

    protected initTinyMce(): void {
        // @ts-ignore
        tinymce.init({
            selector: 'textarea#editcampaignvouchercodeNotes_field',
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

    onFormSubmit(e: any) {
        e.preventDefault();
        let data: ReloadData = this.$form.getFormData();
        this.$submitBtn.hide();
        this.$loading.show();

        axios.post('/staff/campaigns/save', data, {
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

        this.$submitBtn.prop('disabled', true);

        setTimeout(() => {
            // @ts-ignore
            window.location.href = `${TN.BASE_URL}staff/campaigns`;
        }, 1000);
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
        new ErrorToast(response.data.error);
    }

}