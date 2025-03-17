import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import {Modal} from "bootstrap";
import _ from "lodash";

export default class ListPageEntries extends HTMLComponent {

    protected updateUrlQueryOnReload: boolean = true;
    private saveModal: Modal;
    private $pathFilterInput: Cash;
    private $titleFilterInput: Cash;
    private $tagFilterInput: Cash;
    private $searchFilterInput: Cash;
    private $noTagsInput: Cash;
    private $noThumbnailInput: Cash;
    private $filterForm: Cash;
    
    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination')
        ];
        this.observeControls();
        this.$element.find('#edit_page_entry_modal').on('pageEntrySaved', this.onPageEntrySaved.bind(this));
        this.saveModal = new Modal(this.$element.find('#edit_page_entry_modal').get(0));
        this.$pathFilterInput = this.$element.find('input[name=path]');
        this.$titleFilterInput = this.$element.find('input[name=title]');
        this.$tagFilterInput = this.$element.find('input[name=tag]');
        this.$searchFilterInput = this.$element.find('input[name=search]');
        this.$noTagsInput = this.$element.find('input[name=notags]');
        this.$noThumbnailInput = this.$element.find('input[name=nothumbnail]');
        this.$filterForm = this.$element.find('form.filter-form');
        this.$filterForm.on('submit', this.onFilterFormSubmit.bind(this));
        this.$element.find('a.edit-page-entry').on('click', this.onEditPageEntryClick.bind(this));
    }

    protected onFilterFormSubmit(e: any): void {
        e.preventDefault();
        this.reload();
    }

    protected onPageEntrySaved(): void {
        this.saveModal.hide();
        this.reload();
    }

    protected getReloadData(): ReloadData {
        let data: ReloadData = super.getReloadData();
        data.filter_path = this.$pathFilterInput.val() ?? '';
        data.filter_title = this.$titleFilterInput.val() ?? '';
        data.filter_search = this.$searchFilterInput.val() ?? '';

        if (data.filter_search.length) {
            this.$tagFilterInput.val('');
        }

        data.filter_tag = this.$tagFilterInput.val() ?? '';
        data.filter_notags = this.$noTagsInput.is(':checked') ? 1 : 0;
        data.filter_nothumbnail = this.$noThumbnailInput.is(':checked') ? 1 : 0;

        this.$filterForm.find('input.content-filter').each(function(i, input) {
            let $input = $(input);
            data[$input.attr('name') + '_filter'] = $input.is(':checked') ? 1 : 0;
        });

        return data;
    }

    protected onEditPageEntryClick(e: any): void {
        $('#edit_page_entry_modal').data('pageEntryId', _.parseInt($(e.target).parents('tr').data('pageentryid')));
        $('#edit_page_entry_modal').trigger('pageEntryChanged');
    }


    
}