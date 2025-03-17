import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import $, {Cash} from "cash-dom";

export default class ListAdverts extends HTMLComponent {

    private deleteId: number;
    private $modal: Cash;

    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination'),
            this.$element.find('#title_search_field')
        ];
        this.observeControls();
        this.$modal = this.$element.find('#delete_advert_modal');
        this.$modal.get(0).addEventListener('show.bs.modal', this.onModalShow.bind(this));
        this.$modal.find('button.btn-danger').on('click', this.onDeleteAdvert.bind(this));
    }

    protected onModalShow(event: any): void {
        const button = $(event.relatedTarget);
        this.$modal.find('.advert-title').text(button.data('advert-title'));
        this.$modal.find('button.btn-danger').data('advert-id', button.data('advert-id'));
    }

    protected onDeleteAdvert(event: any): void {
        this.deleteId = $(event.target).data('advert-id');
        this.reload();
    }

    protected setReloading(): void {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    protected getReloadData(): ReloadData {
        const data = super.getReloadData();

        if (this.deleteId) {
            data['deleteId'] = this.deleteId;
        }

        return data;
    }
}