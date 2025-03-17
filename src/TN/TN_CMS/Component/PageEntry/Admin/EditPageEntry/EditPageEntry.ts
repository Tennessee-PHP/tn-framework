import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';
import axios, {AxiosError} from "axios";

export default class EditPageEntry extends HTMLComponent {
    private $saveBtn: Cash;
    private $saveBtnLabel: Cash;
    private $saveBtnLoading: Cash;
    private $titleInput: any;
    private $subtitleInput: any;
    private $descriptionTextarea: Cash;
    private $weightSelect: Cash;
    private lastSaveData: ReloadData;
    
    protected observe(): void {
        $('#edit_page_entry_modal').on('pageEntryChanged', this.reload.bind(this));
        this.$saveBtn = this.$element.find('#save_edit_page_entry_button');
        this.$saveBtnLabel = this.$saveBtn.find('.label');
        this.$saveBtnLoading = this.$saveBtn.find('.spinner-border');
        this.$saveBtn.on('click', this.onSaveBtn.bind(this));
        this.$titleInput = this.$element.find('#edit_page_entry_title');
        this.$subtitleInput = this.$element.find('#edit_page_entry_subtitle');
        this.$descriptionTextarea = this.$element.find('#edit_page_entry_description');
        this.$weightSelect = this.$element.find('#edit_page_entry_weight');
        this.$element.find('#page_entry_thumbnail').on('change', this.onUploadImage.bind(this, 'thumbnail'));
        this.$element.find('#page_entry_vthumbnail').on('change', this.onUploadImage.bind(this, 'vthumbnail'));
    }

    protected getReloadData(): ReloadData {
        let data: ReloadData = super.getReloadData();
        data.pageEntryId = $('#edit_page_entry_modal').data('pageEntryId');
        return data;
    }

    protected setSaveLoading(loading: any): void {
        if (loading) {
            this.$saveBtnLabel.addClass('d-none');
            this.$saveBtnLoading.removeClass('d-none');
            this.$saveBtn.attr('disabled', 'disabled');
        } else {
            this.$saveBtnLabel.removeClass('d-none');
            this.$saveBtnLoading.addClass('d-none');
            this.$saveBtn.attr('disabled', null);
        }
    }

    protected async onUploadImage(field: any) {
        let formId = 'page_entry_' + field + '_form';

        /*var formData = new FormData();
        var imagefile = document.querySelector('#file');
        formData.append("image", imagefile.files[0]);*/
        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/upload-image', new FormData($('#' + formId).get(0)), {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: any) => {
                $('#page_entry_' + field + '_src').val(response.data.location);
                $('#page_entry_' + field + '_src_img').attr('src', response.data.location).removeClass('d-none');
            })
            .catch((error: any) => {
                new ErrorToast(error.data.message);
            });

        /*

        try {
            // @ts-ignore
            let response: Response = await fetch(TN.BASE_URL + 'staff/upload-image', {
                method: "POST",
                // @ts-ignore
                body: new FormData($('#' + formId).get(0))
            });

            let data: any = await response.json();
            console.log(field, data);

            // set the hidden field
            $('#page_entry_' + field + '_src').val(data.location);

            // show the image; set its src
            $('#page_entry_' + field + '_src_img').attr('src', data.location).removeClass('d-none');

        } catch (e) {
            new ErrorToast(e);
        }*/
    }

    onSaveBtn() {
        this.setSaveLoading(true);
        this.lastSaveData = {
            title: this.$titleInput.val(),
            subtitle: this.$subtitleInput.val(),
            description: this.$descriptionTextarea.val(),
            weight: this.$weightSelect.val(),
            pageEntryId: this.$element.attr('data-pageEntryId'),
            tags: this.$element.find('.tn-tn_cms-component-tageditor-tageditor').data('tags'),
            thumbnailSrc: $('#page_entry_thumbnail_src').val(),
            vThumbnailSrc: $('#page_entry_vthumbnail_src').val()
        };

        console.log(this.lastSaveData);

        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/page-entries/save', this.lastSaveData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(this.onSaveSuccess.bind(this))
            .catch(this.onSaveError.bind(this));
    }

    onSaveSuccess(response: any) {
        // set loading, close the modal and send a note to the table to update itself
        this.setSaveLoading(false);
        let data = response.data;
        data.pageEntryId = this.lastSaveData.pageEntryId;
        this.$element.trigger('pageEntrySaved', data);
        this.$element.find('.btn-close').get(0).click();
    }

    onSaveError(error: any) {
        this.setSaveLoading(false);
        new ErrorToast(error.response.data.message);
    }
    
}