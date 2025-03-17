import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";
import axios, {AxiosError, AxiosResponse} from "axios";
import ErrorToast from "@tn/TN_Core/Component/Toast/ErrorToast";
import SuccessToast from "@tn/TN_Core/Component/Toast/SuccessToast";

export default class EditAdvert extends HTMLComponent {
    private tinyMceEditor: any;
    private $form: Cash;
    private $saveButton: Cash;
    
    protected observe(): void {
        this.$form = this.$element.find('#edit_advert');
        this.$form.on('submit', this.onFormSubmit.bind(this));
        this.$saveButton = this.$element.find('#save_advert_button');
        this.$saveButton.on('click', this.onFormSubmit.bind(this));

        const $description = this.$form.find('[name="description"]');

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
            // @ts-ignore
            images_upload_url: TN.BASE_URL + 'staff/upload-image',
            images_upload_credentials: true,
            automatic_uploads: true,
            toolbar_sticky: true,
            init_instance_callback: this.observeTinyMce.bind(this),
            toolbar_sticky_offset: 50,
            plugins: 'lists, link, image, media, autoresize, help, code, preview, emoticons',
            external_plugins: {
                // @ts-ignore
                'tnadverts': TN.BASE_URL + 'tnstatic/lib/tinymce-tnadverts-plugin/plugin.js'
            },
            toolbar: [
                'bold italic underline | removeformat | h1 h2 h3 h4 h5 | bullist numlist | alignleft aligncenter alignright',
                'hero banner | formatgroup paragraphgroup link image media emoticons | code help'
            ],
            menubar: false
        });
    }

    observeTinyMce(editor: any) {
        this.tinyMceEditor = editor;
    }

    onFormSubmit(event: Event) {
        event.preventDefault();
        this.$saveButton.prop('disabled', true);
        this.$saveButton.find('.spinner-border').removeClass('d-none');

        let data: ReloadData = this.$form.getFormData();
        data.advert = this.tinyMceEditor.getContent({format: 'html'});
        axios.post(this.$form.attr('action'), data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    $('#advert_id_field').val(response.data.advertId);
                    new SuccessToast(response.data.message);
                }
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response.data.message);
            })
            .finally((): void => {
                this.$saveButton.prop('disabled', false);
                this.$saveButton.find('.spinner-border').addClass('d-none');
            });
    }
    
}