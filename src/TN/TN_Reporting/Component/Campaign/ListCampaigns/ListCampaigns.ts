import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData, ReloadMethod} from '@tn/TN_Core/Component/HTMLComponent';

export default class ListCampaigns extends HTMLComponent {

    protected reloadMethod: ReloadMethod = 'post';
    private toggleArchiveCampaignId: number|null = null;

    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination')
        ];
        this.observeControls();
        
        this.$element.on('click', '.toggle-archive-btn', this.onToggleArchiveClick.bind(this));
    }
    
    protected onToggleArchiveClick(event: Event): void {
        const $button = $(event.target as HTMLElement).closest('.toggle-archive-btn');
        this.toggleArchiveCampaignId = parseInt($button.data('campaign-id'));
        this.reload();
    }

    protected getReloadData(): ReloadData {
        let data = super.getReloadData();
        if (this.toggleArchiveCampaignId) {
            data.toggleArchiveCampaignId = this.toggleArchiveCampaignId;
        }
        return data;
    }
}