import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import axios, {AxiosError, AxiosResponse} from "axios";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class ListArticles extends HTMLComponent {

    protected updateUrlQueryOnReload: boolean = true;
    private $publishWarningModal: Cash;
    private $deleteModal: Cash;
    private $deleteTr: Cash;

    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination'),
            this.$element.find('.tn-tn_core-component-user-select-userselect-userselect'),
            this.$element.find('.tn-tn_cms-component-article-admin-articlestateselect-articlestateselect')
        ];
        this.observeControls();
        this.$element.find('select.weight').on('change', this.onEditWeightSelect.bind(this));
        this.$element.find('a.delete').on('click', this.onDeleteBtn.bind(this));
        this.$element.find('a.publish').on('click', this.onPublishWarning.bind(this));
        //this.$el.find('a.reload-sortable-col-link').click(this.onSortableColClick.bind(this));
    }

    protected setReloading(reloading: Boolean): void {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        super.setReloading(reloading);
    }

    onEditWeightSelect(event: any): void {
        let data: ReloadData = {
            weight: $(event.target).val(),
            articleId: $(event.target).parents('tr').attr('data-articleid')
        }
        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/articles/edit-article-weight', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                }
            })
            .catch((error: AxiosError): void => {
                // @ts-ignore
                new ErrorToast(error.response.data.message);
            });
    }

    onPublishWarning() {
        this.$publishWarningModal = $("#publishedwarningmodal");
    }

    onDeleteBtn(event: any) {
        this.$deleteModal = $("#deletearticlemodal");
        this.$deleteTr = $(event.target).parents('tr');
        this.$deleteModal.attr("data-articleid", $(event.target).parents('tr').attr('data-articleid'));
        $("#deletearticlemodal .article-title").text($(event.target).parents('tr').attr('data-articletitle'));
        this.$deleteModal.find("button.delete-confirm").on('click', this.onDeleteConfirmBtn.bind(this));
    }

    onDeleteConfirmBtn() {
        this.$deleteTr.addClass('table-secondary');
        let data: ReloadData = {
            delete: true,
            articleId: $("#deletearticlemodal").attr("data-articleid")
        }
        // @ts-ignore
        axios.post(TN.BASE_URL + 'staff/articles/delete-article', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response: AxiosResponse): void => {
                this.$deleteTr.remove();
                if (response.data.result === 'success') {
                    new SuccessToast(response.data.message);
                }
            })
            .catch((error: AxiosError): void => {
                this.$deleteTr.show();
                // @ts-ignore
                new ErrorToast(error.response.data.message);
            });
    }
    
}