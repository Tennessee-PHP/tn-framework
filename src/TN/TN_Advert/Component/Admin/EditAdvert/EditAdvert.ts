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
    private $tabButtons: Cash;
    private $tabPanels: Cash;
    private $scriptTextarea: Cash;
    private activeEditorType: 'tinymce' | 'script' = 'tinymce';
    
    protected observe(): void {
        this.$form = this.$element.find('#edit_advert');
        this.$form.on('submit', this.onFormSubmit.bind(this));
        this.$saveButton = this.$element.find('#save_advert_button');
        this.$saveButton.on('click', this.onFormSubmit.bind(this));
        this.$tabButtons = this.$element.find('[data-editor-tab]');
        this.$tabPanels = this.$element.find('[data-editor-panel]');
        this.$scriptTextarea = this.$element.find('#script_editor');

        const initialEditorType = (this.$element.data('editor-type') as string) ?? 'tinymce';
        this.setActiveEditorType(initialEditorType);
        this.$tabButtons.on('click', this.onTabClick.bind(this));

        const $description = this.$form.find('[name="description"]');

        // @ts-ignore
        tinymce.init({
            selector: 'textarea#editor',
            skin: 'bootstrap',
            content_css: [
                'default',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
                // @ts-ignore
                TN.CSS_URL
            ],
            setup: (editor: any) => {
                editor.on('init', () => {
                    const editorIframe = editor.iframeElement;
                    if (editorIframe) {
                        const iframeDoc = editorIframe.contentDocument;
                        
                        // Add data-bs-theme attribute to body
                        iframeDoc.body.setAttribute('data-bs-theme', 'light');
                        
                        // Add font preconnect and stylesheet links
                        const head = iframeDoc.head;
                        
                        // Add preconnect links
                        const preconnectGoogle = iframeDoc.createElement('link');
                        preconnectGoogle.rel = 'preconnect';
                        preconnectGoogle.href = 'https://fonts.googleapis.com';
                        head.appendChild(preconnectGoogle);

                        const preconnectGstatic = iframeDoc.createElement('link');
                        preconnectGstatic.rel = 'preconnect';
                        preconnectGstatic.href = 'https://fonts.gstatic.com';
                        preconnectGstatic.setAttribute('crossorigin', '');
                        head.appendChild(preconnectGstatic);

                        // Add font stylesheet
                        // @ts-ignore
                        const fontUrls: string[] = TN.FONT_URLS;
                        fontUrls.forEach(url => {
                            const fontLink = iframeDoc.createElement('link');
                            fontLink.rel = 'stylesheet';
                            fontLink.href = url;
                            head.appendChild(fontLink);
                        });
                    }
                });
            },
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
            menubar: false,
            valid_elements: '*[*]',
            extended_valid_elements: 'script[src|async|defer|type|charset|crossorigin],ins[class|style|data-ad-client|data-ad-slot|data-ad-format|data-full-width-responsive|data-adsbygoogle-status]',
            valid_children: '+body[style|script|ins],+div[script|ins]',
            forced_root_block: false,
            force_p_newlines: false,
            force_br_newlines: true,
            convert_newlines_to_brs: false,
            remove_linebreaks: false,
            apply_source_formatting: false,
            paste_block_drop: false,
            paste_merge_formats: false
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
        if (this.activeEditorType === 'script') {
            const scriptValue = this.$scriptTextarea.val();
            data.advert = typeof scriptValue === 'string' ? scriptValue : '';
            data.editorType = 'script';
        } else {
            const editorContent = this.tinyMceEditor ? this.tinyMceEditor.getContent({format: 'html'}) : '';
            data.advert = editorContent;
            data.editorType = 'tinymce';
        }
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
    
    private onTabClick(event: Event): void {
        event.preventDefault();
        const target = event.currentTarget as HTMLElement;
        const editorType = target.getAttribute('data-editor-tab');
        this.setActiveEditorType(editorType ?? 'tinymce');
    }

    private setActiveEditorType(editorType: string): void {
        const normalisedEditorType: 'tinymce' | 'script' = editorType === 'script' ? 'script' : 'tinymce';
        this.activeEditorType = normalisedEditorType;

        this.$tabButtons.each((index: number, element: HTMLElement) => {
            const $button = $(element);
            const isActive = $button.data('editor-tab') === normalisedEditorType;
            $button.toggleClass('active', isActive);
            $button.attr('aria-selected', isActive ? 'true' : 'false');
        });

        this.$tabPanels.each((index: number, element: HTMLElement) => {
            const $panel = $(element);
            const isActive = $panel.data('editor-panel') === normalisedEditorType;
            $panel.toggleClass('active', isActive);
            $panel.toggleClass('show', isActive);
        });
    }
}