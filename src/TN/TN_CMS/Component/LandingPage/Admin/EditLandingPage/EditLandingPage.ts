import HTMLComponent, { ReloadData } from '@tn/TN_Core/Component/HTMLComponent';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';
import axios, { AxiosError, AxiosResponse } from 'axios';
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import _ from 'lodash';
import $, { Cash } from 'cash-dom';

interface ErrorResponse {
    message: string;
    data?: {
        message: string;
    };
}

declare global {
    interface Window {
        tinymce: {
            init(config: any): void;
        };
    }
}

export default class EditLandingPage extends HTMLComponent {
    protected $element: Cash;
    private editRequests: Array<{ data: any; options: any }> = [];
    private landingPageId: string | number;
    private editRequestLoading: boolean = false;
    private $saveStatusContainer: Cash;
    private $saveBtn: Cash;
    private $tagEditor: Cash;
    private tinyMceEditor: any;
    private imgSrcs: string[] | null = null;
    private grabImagesDelay?: number;
    private saveLandingPageContentDelay?: number;
    private $landingPageTitleEditor: Cash;

    protected observe(): void {
        this.$saveStatusContainer = this.$element.find('.save-status-container');
        this.$saveBtn = this.$element.find('#save_landing_page_btn');
        this.$landingPageTitleEditor = this.$element.find(
            '.tn-tn_cms-component-landingpage-admin-editlandingpage-landingpagetitleeditor-landingpagetitleeditor'
        );
        this.$tagEditor = this.$element.find('.tn-tn_cms-component-tageditor-tageditor');
        this.landingPageId = this.$element.data('landingpageid');
        if (!this.landingPageId) {
            this.landingPageId = 'new';
        }
        this.editRequests = [];
        this.editRequestLoading = false;
        this.addLandingPageIdToHref(this.landingPageId);

        const $form = this.$element.find('form');
        const $description = $form.find('[name="description"]');
        const $title = $form.find('[name="title"]');
        const $subtitle = $form.find('[name="subtitle"]');
        const $originalTitle = $form.find('[name="originalTitle"]');
        const $originalSubtitle = $form.find('[name="originalSubtitle"]');
        const $originalDescription = $form.find('[name="originalDescription"]');
        const $optionsButton = this.$element.find('.landing-page-options-button');
        const $optionsModal = this.$element.find('#landing_page_options_modal');
        const $optionsForm = $optionsModal.find('form');

        const formEl = $form[0];
        const optionsFormEl = $optionsForm[0];
        
        if (!(formEl instanceof HTMLFormElement) || !(optionsFormEl instanceof HTMLFormElement)) {
            return;
        }

        const data = new FormData(formEl);
        const optionsData = new FormData(optionsFormEl);

        // @ts-ignore
        window.tinymce.init({
            selector: '#landingpage_description',
            height: 500,
            menubar: false,
            plugins: [
                'advlist',
                'autolink',
                'lists',
                'link',
                'image',
                'charmap',
                'preview',
                'anchor',
                'searchreplace',
                'visualblocks',
                'code',
                'fullscreen',
                'insertdatetime',
                'media',
                'table',
                'help',
                'wordcount',
                'tncms',
            ],
            toolbar:
                'undo redo | blocks | ' +
                'bold italic | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help | tncms',
            contextmenu: 'bootstrap',
            external_plugins: {
                // @ts-ignore
                tncms: TN.BASE_URL + 'tnstatic/lib/tinymce-tncms-plugin/plugin.js',
            },
            skin: 'bootstrap',
            body_attributes: {
                'data-bs-theme': 'light'
            },
            content_css: [
                'default',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
                // @ts-ignore
                TN.CSS_URL
            ],
        });

        this.initTinyMce();

        this.$element.find('input.landing-page-title').on('change', this.onUnsavedChange.bind(this));
        this.$element.find('#landing_page_state').on('change', this.onUnsavedChange.bind(this));
        this.$element.find('#landing_page_url').on('change', this.onUnsavedChange.bind(this));
        this.$element.find('#allow_removed_navigation').on('change', this.onUnsavedChange.bind(this));
        this.$element.find('#landing_page_convertkit_tag').on('change', this.onUnsavedChange.bind(this));
        this.$element.find('#landing_page_campaign').on('change', this.onUnsavedChange.bind(this));
        this.$element.find('#landing_page_image').on('change', this.onUploadImage.bind(this));

        this.$saveBtn.on('click', this.onSave.bind(this));

        this.$landingPageTitleEditor.on('change', this.onChange.bind(this));

        this.$tagEditor.on('change', (e: Event, tags: string[]) => {
            this.onChange(e, { tags: this.$tagEditor.data('tags') });
        });
    }

    protected onUploadImage(): void {
        const form = $('#landing_page_image_form')[0];
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        // @ts-ignore
        axios
                // @ts-ignore
            .post(TN.BASE_URL + 'staff/upload-image', new FormData(form), {
            headers: {
                    'Content-Type': 'multipart/form-data',
                },
        })
            .then((response: any) => {
                this.$element.find('#landing_page_thumbnail_src').val(response.data.location);
                this.$element
                    .find('section.landing-page-header-1')
                    .css('background-image', 'url("' + response.data.location + '")');
                this.onUnsavedChange();
            })
            .catch((error: any) => {
                // @ts-ignore
                new ErrorToast(error.data.message);
            });
    }

    protected addLandingPageIdToHref(landingPageId: string | number): void {
        let thisUrl = new URL(document.location.href);
        thisUrl.searchParams.set('landingpageid', landingPageId.toString());
        history.pushState(null, '', thisUrl.href);
    }

    protected onUnsavedChange(): void {
        this.$saveBtn.prop('disabled', false);
    }

    protected onSave(): void {
        let edit: ReloadData = {};
        edit.title = this.$element.find('input.landing-page-title').val();
        edit.state = this.$element.find('#landing_page_state').val();
        edit.urlStub = this.$element.find('#landing_page_url').val();
        edit.content = this.tinyMceEditor.getContent({ format: 'html' });
        edit.tags = this.$tagEditor.data('tags');
        edit.allowRemovedNavigation = this.$element.find('#allow_removed_navigation').prop('checked') ? 1 : 0;
        edit.convertKitTag = this.$element.find('#landing_page_convertkit_tag').val();
        edit.campaignId = this.$element.find('#landing_page_campaign').val();
        edit.thumbnailSrc = this.$element.find('#landing_page_thumbnail_src').val();

        this.$saveBtn.find('.spinner-border').removeClass('d-none');
        this.$saveBtn.find('i.bi').addClass('d-none');

        this.onChange(null, edit);
    }

    protected onChange(e: Event, data: ReloadData): void {
        if (!data) {
            return;
        }

        let options: any = _.pick(data, ['success', 'error', 'complete']);
        _.defaults(options, {
            success: () => {},
            error: () => {},
            complete: () => {},
        });

        delete data.success;
        delete data.error;
        delete data.complete;

        if (data.primarySeoKeyword) {
            this.notifyLandingPageSeoChecklist();
        }

        this.editRequests.push({
            data: data,
            options: options,
        });

        this.nextEditRequest();
    }

    protected setSaveStatus(status: string): void {
        this.$saveStatusContainer.find('p').addClass('d-none');
        this.$saveStatusContainer.find('p.save-status-' + status).removeClass('d-none');
    }

    protected notifyLandingPageSeoChecklist(): void {
        // This method will be implemented later
    }

    protected onError(error: AxiosError<ErrorResponse>): void {
        // @ts-ignore
        const message = error.response?.data?.message || error.message;
        new ErrorToast(message);
        this.setSaveStatus('error');
    }

    protected nextEditRequest(): void {
        if (this.editRequestLoading || !this.editRequests.length) {
            return;
        }

        this.editRequestLoading = true;

        const { data, options } = this.editRequests.shift()!;

        this.setSaveStatus('saving');

        // @ts-ignore
        axios
            .post(
                // @ts-ignore
                TN.BASE_URL +
                    'staff/landingpages/edit/save' +
                    (this.landingPageId !== 'new' ? '?landingpageid=' + this.landingPageId : ''),
                data,
                {
            headers: {
                        'Content-Type': 'multipart/form-data',
                    },
            }
            )
            .then((response: AxiosResponse): void => {
                this.$saveBtn.find('.spinner-border').addClass('d-none');
                this.$saveBtn.find('i.bi').removeClass('d-none');
                this.setSaveStatus('saved');
                let data = response.data;
                options.success(data);
                this.landingPageId = data.landingPageId;
                this.$element.attr('data-landingpageid', data.landingPageId);
                // @ts-ignore
                this.$element
                    .find('a.landingpage-preview-link')
                    // @ts-ignore
                    .attr('href', TN.BASE_URL + data.landingPageUrl + '?preview=1');
                this.addLandingPageIdToHref(data.landingPageId);
            })
            .catch((error: AxiosError<ErrorResponse>): void => {
                this.onError(error);
                options.error(error.response);
            })
            .finally((): void => {
                this.editRequestLoading = false;
                options.complete();
                _.defer(this.nextEditRequest.bind(this), 500);
            });
    }

    protected initTinyMce(): void {
        let toolbar =
            'landingpageinsiderroadblock | bold italic underline | removeformat | h2 h3 h4 h5 | bullist numlist | alignleft aligncenter alignright | forecolor backcolor | link image media table code';
        // @ts-ignore
        window.tinymce.init({
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
                editor.on('LoadContent', () => {
                });
            },
            init_instance_callback: (editor: any) => {
                this.tinyMceEditor = editor;
                this.tinyMceEditor.on('Change', this.onUnsavedChange.bind(this));
            },
            relative_urls: false,
            convert_urls: false,
            remove_script_host: false,
            // @ts-ignore
            images_upload_url: TN.BASE_URL + 'staff/upload-image',
            images_upload_credentials: true,
            automatic_uploads: true,
            toolbar_sticky: true,
            toolbar_sticky_offset: 50,
            min_height: 1000,
            plugins: 'lists, link, image, media, mediaembed, autoresize, code, autolink, powerpaste, table',
            mediaembed_max_width: 450,
            toolbar: [toolbar],
            menubar: false,
            contextmenu: 'bootstrap',
            external_plugins: {
                // @ts-ignore
                tncms: TN.BASE_URL + 'fbgstatic/lib/tinymce-tncms-plugin/plugin.js?td=2'
            },

            // image options
            images_reuse_filename: true,
            image_caption: true,
            images_default_classes: 'img-fluid',
            image_class_list: [
                { title: 'None', value: '' },
                { title: 'Responsive (scales to fit width)', value: 'responsive' },
                { title: 'Float right', value: 'float-end' },
                { title: 'Float left', value: 'float-start' },
                { title: 'Fluid', value: 'img-fluid' }
            ],

            // power paste options
            powerpaste_word_import: 'clean',
            powerpaste_googledocs_import: 'clean',
            powerpaste_html_import: 'clean',
            powerpaste_allow_local_images: false,
            powerpaste_block_drop: true,

            // table options
            table_clone_elements: 'strong em a',
            table_header_type: 'sectionCells',
            table_sizing_mode: 'responsive',
            table_column_resizing: 'preservetable',
            table_resize_bars: false,
            object_resizing: 'img',
            table_advtab: false,
            table_row_advtab: false,
            table_cell_advtab: false,
            table_toolbar: 'tabledelete',
            table_appearance_options: false,
            table_grid: false,
            table_border_styles: [{ title: 'None', value: '' }],
            table_class_list: [{ title: 'None', value: '' }],
            table_cell_class_list: [
                { title: 'None', value: '' },
                { title: 'Primary', value: 'table-primary' },
                { title: 'Secondary', value: 'table-secondary' },
                { title: 'Success', value: 'table-success' },
                { title: 'Danger', value: 'table-danger' },
                { title: 'Warning', value: 'table-warning' },
                { title: 'Light', value: 'table-light' },
                { title: 'Dark', value: 'table-dark' },
            ],
            table_row_class_list: [
                { title: 'None', value: '' },
                { title: 'Primary', value: 'table-primary' },
                { title: 'Secondary', value: 'table-secondary' },
                { title: 'Success', value: 'table-success' },
                { title: 'Danger', value: 'table-danger' },
                { title: 'Warning', value: 'table-warning' },
                { title: 'Light', value: 'table-light' },
                { title: 'Dark', value: 'table-dark' },
            ],
            table_border_widths: [{ title: 'None', value: 0 }],

            paste_postprocess: this.tinyMcePastePostProcess.bind(this)
        });
    }

    protected observeTinyMce(editor: any): void {
        this.tinyMceEditor = editor;
        this.tinyMceEditor.on('Change', this.onUnsavedChange.bind(this));
    }

    protected tinyMcePastePostProcess(pluginApi: any, data: any): void {
        // move the first tr of each tbody into a thead, if the table does not have a thead
        $(data.node)
            .find('table')
            .each(function (i, table) {
            let $table = $(table);
            $table.addClass('table');
            $table.attr('data-sheets-root', null);
            $table.attr('border', '0');
            $table.attr('cellpadding', '0');
            $table.attr('cellspacing', '0');

            $table.find('tr:first-child td').each(function () {
                $(this).replaceWith('<th>' + $(this).html() + '</th>');
            });

            if (!$table.find('thead').length && !$table.find('th').length) {
                $table.prepend('thead').add($table.remove('tr:first-child'));
            }

            $table.find('tr, td').attr('style', null);
            $table.find('tr, td').attr('data-sheets-value', null);
            $table.find('tr, td').attr('data-sheets-formula', null);
        });
    }
}
