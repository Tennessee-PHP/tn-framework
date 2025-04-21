import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';
import axios, {AxiosError, AxiosResponse} from "axios";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import _ from "lodash";
import $, {Cash} from "cash-dom";

export default class EditArticle extends HTMLComponent {
    protected $element: Cash;
    private editRequests: Array<{data: any, options: any}> = [];
    private articleId: string | number;
    private editRequestLoading: boolean = false;
    private $saveStatusContainer: Cash;
    private tinyMceEditor: any;
    private imgSrcs: string[] | null = null;
    private grabImagesDelay?: number;
    private saveArticleContentDelay?: number;
    private $articleTitleEditor: Cash;
    private $tagEditor: Cash;
    private $articleMetadataEditor: Cash;

    private $articleSeoChecklist: Cash;
    protected observe(): void {

        this.$saveStatusContainer = this.$element.find('.save-status-container');
        this.$articleTitleEditor = this.$element.find('.tn-tn_cms-component-article-admin-editarticle-articletitleeditor-articletitleeditor');
        this.$tagEditor = this.$element.find('.tn-tn_cms-component-tageditor-tageditor');
        this.$articleMetadataEditor = this.$element.find('.tn-tn_cms-component-article-admin-editarticle-articlemetadataeditor-articlemetadataeditor');
        this.$articleSeoChecklist = this.$element.find('.tn-tn_cms-component-article-admin-editarticle-articleseochecklist-articleseochecklist');
        
        const $form = this.$element.find('form');
        const $description = $form.find('[name="description"]');
        const $title = $form.find('[name="title"]');
        const $subtitle = $form.find('[name="subtitle"]');
        const $originalTitle = $form.find('[name="originalTitle"]');
        const $originalSubtitle = $form.find('[name="originalSubtitle"]');
        const $originalDescription = $form.find('[name="originalDescription"]');

        // @ts-ignore
        window.tinymce.init({
            selector: '#article_description',
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount', 'tncms'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help | tncms',
            contextmenu: 'bootstrap',
            external_plugins: {
                // @ts-ignore
                'tncms': TN.BASE_URL + 'tnstatic/lib/tinymce-tncms-plugin/plugin.js'
            },
            content_css: [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
                // @ts-ignore
                TN.CSS_URL
            ]
        });

        this.addAutoResize();
        this.initTinyMce();
        this.articleId = this.$element.data('articleid');
        this.editRequests = [];
        this.editRequestLoading = false;
        this.addArticleIdToHref(this.articleId);

        this.$articleTitleEditor.on('change', this.onChange.bind(this));
        this.$articleMetadataEditor.on('change', this.onChange.bind(this));
        this.$articleSeoChecklist.on('change', this.onChange.bind(this));

        this.$tagEditor.on('change', (e: Event, tags: string[]) => {
            this.onChange(e, { tags: this.$tagEditor.data('tags') });
        });
    }

    protected addAutoResize(): void {
        $('textarea.auto-resize').each(this.addSingleAutoResize.bind(this));
    }

    protected addSingleAutoResize(i: number, e: HTMLElement): void {
        e.style.overflow = 'hidden';
        this.autoResize(e);
        $(e).on('input', this.onAutoResizeInput.bind(this));
    }

    protected onAutoResizeInput(e: Event): void {
        this.autoResize(e.target as HTMLElement);
    }

    protected autoResize(e: HTMLElement): void {
        e.style.height = '';
        e.style.height = e.scrollHeight + 'px';
    }

    protected addArticleIdToHref(articleId: string | number): void {
        let thisUrl = new URL(document.location.href);
        thisUrl.searchParams.set('articleid', articleId.toString());
        history.pushState(null, '', thisUrl.href);
    }

    protected saveArticleContent(): void {
        this.onChange({} as Event, {
            'content': this.tinyMceEditor.getContent({format: 'html'})
        });
    }

    protected onChange(e: Event, data: ReloadData): void {

        if (!data) {
            return;
        }

        let options: any = _.pick(data, ['success', 'error', 'complete']);
        _.defaults(options, {
            success: () => {},
            error: () => {},
            complete: () => {}
        });

        delete data.success;
        delete data.error;
        delete data.complete;

        if (data.primarySeoKeyword) {
            this.notifyArticleSeoChecklist();
        }

        this.editRequests.push({
            data: data,
            options: options
        });

        this.nextEditRequest();
    }

    protected setSaveStatus(status: string): void {
        this.$saveStatusContainer.find('p').addClass('d-none');
        this.$saveStatusContainer.find('p.save-status-' + status).removeClass('d-none');
    }

    protected onError(error: AxiosError): void {
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

        const {data, options} = this.editRequests.shift()!;

        this.setSaveStatus('saving');

        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/articles/edit/save' + (this.articleId !== 'new' ? '?articleid=' + this.articleId : ''), data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                this.setSaveStatus('saved');
                let data = response.data;
                this.articleId = data.articleId;
                this.$element.attr('data-articleid', data.articleId);
                // @ts-ignore
                this.$element.find('a.article-preview-link').attr('href', TN.BASE_URL + data.articleUrl + '?preview=1');
                this.addArticleIdToHref(data.articleId);
                options.success(data);
            })
            .catch((error: AxiosError): void => {
                this.onError(error);
                options.error(error.response);
            })
            .finally((): void => {
                this.editRequestLoading = false;
                options.complete();
                _.defer(this.nextEditRequest.bind(this), 500);
            });
    }

    protected initTinyMce() {
        let toolbar = 'roadblock | advertisement | bold italic underline | removeformat | h2 h3 | bullist numlist | alignleft aligncenter alignright | forecolor link image media table';
        if (this.$element.data('user-is-article-editor') == '1') {
            toolbar += ' code';
        }
        toolbar += '| addcomment showcomments';
        // @ts-ignore
        window.tinymce.init({
            selector: 'textarea#editor',
            skin: 'bootstrap',
            content_css: [
                // @ts-ignore
                TN.CSS_URL,
                // @ts-ignore
                TN.FONTS_CSS_URL
            ],
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
            plugins: 'tinycomments, lists, link, image, media, mediaembed, autoresize, code, autolink, powerpaste, table, wordcount',
            mediaembed_max_width: 450,
            toolbar: [
                toolbar,
            ],
            menubar: false,
            contextmenu: 'bootstrap',
            external_plugins: {
                // @ts-ignore
                'tncms': TN.BASE_URL + 'fbgstatic/lib/tinymce-tnadverts-plugin/plugin.js',
                // @ts-ignore
                'tncontent': TN.BASE_URL + 'fbgstatic/lib/tinymce-tnadverts-plugin/plugin.js'
            },

            images_reuse_filename: true,
            image_caption: true,
            image_class_list: [
                {title: 'None', value: ''},
                {title: 'Responsive (scales to fit width)', value: 'responsive'},
                {title: 'Float right', value: 'float-end'},
                {title: 'Float left', value: 'float-start'}
            ],

            powerpaste_word_import: 'clean',
            powerpaste_googledocs_import: 'clean',
            powerpaste_html_import: 'clean',
            powerpaste_allow_local_images: false,
            powerpaste_block_drop: true,

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
            table_border_styles: [
                {title: 'None', value: ''}
            ],
            table_class_list: [
                {title: 'None', value: ''}
            ],
            table_cell_class_list: [
                {title: 'None', value: ''},
                {title: 'Primary', value: 'table-primary'},
                {title: 'Secondary', value: 'table-secondary'},
                {title: 'Success', value: 'table-success'},
                {title: 'Danger', value: 'table-danger'},
                {title: 'Warning', value: 'table-warning'},
                {title: 'Light', value: 'table-light'},
                {title: 'Dark', value: 'table-dark'}
            ],
            table_row_class_list: [
                {title: 'None', value: ''},
                {title: 'Primary', value: 'table-primary'},
                {title: 'Secondary', value: 'table-secondary'},
                {title: 'Success', value: 'table-success'},
                {title: 'Danger', value: 'table-danger'},
                {title: 'Warning', value: 'table-warning'},
                {title: 'Light', value: 'table-light'},
                {title: 'Dark', value: 'table-dark'}
            ],
            table_border_widths: [
                {title: 'None', value: 0}
            ],

            init_instance_callback: this.observeTinyMce.bind(this),
            paste_postprocess: this.tinyMcePastePostProcess.bind(this),

            tinycomments_mode: 'callback',
            tinycomments_create: this.tinyMceCommentsCreateThread.bind(this),
            tinycomments_reply: this.tinyMceCommentsCreateComment.bind(this),
            tinycomments_edit_comment: this.tinyMceCommentsEditComment.bind(this),
            tinycomments_delete: this.tinyMceCommentsDeleteThread.bind(this),
            tinycomments_delete_all: this.tinyMceCommentsDeleteAllThreads.bind(this),
            tinycomments_delete_comment: this.tinyMceCommentsDeleteComment.bind(this),
            tinycomments_lookup: this.tinyMceCommentsLookupThread.bind(this),
            tinycomments_resolve: this.tinyMceCommentsResolveThread.bind(this)
        });
    }

    protected observeTinyMce(editor: any) {
        this.tinyMceEditor = editor;
        this.imgSrcs = null;
        this.tinyMceEditor.on('Change', this.deferGrabImagesFromTinyMce.bind(this));
        this.tinyMceEditor.on('Change', this.deferSaveArticleContent.bind(this));
        this.tinyMceEditor.on('Change', this.notifyArticleSeoChecklist.bind(this));
        this.grabImagesFromTinyMce();
        this.notifyArticleSeoChecklist();
    }

    protected tinyMcePastePostProcess(pluginApi: any, data: any) {
        $(data.node).find('table').each((i: number, element: any) => {
            let $table = $(element);
            $table.addClass('table');
            $table.attr('data-sheets-root', null);
            $table.attr('border', null);
            $table.attr('cellpadding', null);
            $table.attr('cellspacing', null);

            $table.find('tr:first-child td').each((i: number, element: any) => {
                $(element).replaceWith('<th>' + $(element).html() + '</th>');
            });

            if (!$table.find('thead tr').length) {
                if (!$table.find('thead').length) {
                    $table.prepend('<thead></thead>');
                }

                let $thead = $table.find('thead');

                let $tr;
                if ($table.find('tbody tr').length) {
                    $tr = $table.find('tbody tr:first-child');
                    $tr.detach();
                } else {
                    $tr = $table.remove('tr:first-child');
                }
                $tr.appendTo($thead);
            }

            $table.find('tr, td').attr('style', null);
            $table.find('tr, td').attr('data-sheets-value', null);
            $table.find('tr, td').attr('data-sheets-formula', null);

            $table.find('tr, td').each((i: number, element: any) => {
                let $element = $(element);
                let data = $element.data();
                let key;
                for (key in data) {
                    $element.data(key, null);
                }
            });
        });
    }

    protected tinyMceCommentsCreateThread(ref: any, done: any, fail: any) {
        const {content, createdAt} = ref;

        // @ts-ignore
        axios.post(TN.BASE_URL + 'cms/comments/thread',
            {content: content, createdAt: createdAt}, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                done({conversationUid: response.data});
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                fail(error.response.data);
            });
    }

    protected tinyMceCommentsCreateComment(ref: any, done: any, fail: any) {
        const {conversationUid, content, createdAt} = ref;
        // @ts-ignore
        axios.post(TN.BASE_URL + 'cms/comments/thread' + conversationUid + '/comment',
            {content: content, createdAt: createdAt}, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                done({commentUid: response.data});
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                fail(error.response.data);
            });
    }

    protected tinyMceCommentsEditComment(ref: any, done: any, fail: any) {
        const {commentUid, content, modifiedAt} = ref;

        // @ts-ignore
        axios.put(TN.BASE_URL + 'cms/comments/comment/' + commentUid + '/edit',
            {content: content, modifiedAt: modifiedAt}, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then((response: AxiosResponse): void => {
                done(response.data);
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                fail(error.response.data.message);
            });
    }

    protected tinyMceCommentsDeleteThread(ref: any, done: any, fail: any) {
        const conversationUid = ref.conversationUid;

        // @ts-ignore
        axios.delete(TN.BASE_URL + 'cms/comments/thread/' + conversationUid + '/delete')
            .then((response: AxiosResponse): void => {
                done(response.data);
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                fail(error.response.data.message);
            });
    }

    protected tinyMceCommentsDeleteAllThreads(ref: any, done: any) {
        done({
            canDelete: false
        });
    }

    protected tinyMceCommentsDeleteComment(ref: any, done: any, fail: any) {
        const {commentUid} = ref;

        // @ts-ignore
        axios.delete(TN.BASE_URL + 'cms/comments/comment/' + commentUid + '/delete')
            .then((response: AxiosResponse): void => {
                done(response.data);
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                fail(error.response.data.message);
            });
    }

    protected tinyMceCommentsLookupThread(ref: any, done: any, fail: any) {
        const conversationUid = ref.conversationUid;
        // @ts-ignore
        axios.get(TN.BASE_URL + 'cms/comments/thread/' + conversationUid + '/get')
            .then((response: AxiosResponse): void => {
                done(response.data);
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                fail(error.response.data.message);
            });
    }

    protected tinyMceCommentsResolveThread(ref: any, done: any, fail: any) {
        done();
    }

    protected deferGrabImagesFromTinyMce() {
        clearTimeout(this.grabImagesDelay);
        this.grabImagesDelay = _.delay(this.grabImagesFromTinyMce.bind(this), 3000);
    }

    protected deferSaveArticleContent() {
        clearTimeout(this.saveArticleContentDelay);
        this.saveArticleContentDelay = _.delay(this.saveArticleContent.bind(this), 1000);
    }

    protected notifyArticleSeoChecklist() {
        this.$element.find('.tn-tn_cms-component-article-admin-editarticle-articleseochecklist-articleseochecklist').trigger('contentchange', {
            content: this.tinyMceEditor.getContent({format: 'html'})
        });
    }

    protected grabImagesFromTinyMce() {
        let content = this.tinyMceEditor.getContent({format: 'html'});
        let imgSrcs: string[] = [];
        $('#editor_ifr').contents().find('img').each((i: number, img: any) => {
            let $img = $(img);
            let src = $img.attr('src');
            if (src.substring(0, 4) !== 'data') {
                imgSrcs.push(src);
            }
            if (($img.attr('alt') ?? '').trim() === '') {
                $img.attr('alt', "");
            }
            $img.addClass('img-fluid');
        });
        if (JSON.stringify(this.imgSrcs) !== JSON.stringify(imgSrcs)) {
            this.imgSrcs = imgSrcs;
            this.onImgSrcsUpdate();
        }
    }

    protected onImgSrcsUpdate() {
        this.$element.find('.tn-tn_cms-component-article-admin-editarticle-articlethumbnaileditor-articlethumbnaileditor').trigger('imgSrcsUpdate', this.imgSrcs.join('|'));
    }

}