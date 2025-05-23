import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';

export default class Dashboard extends HTMLComponent {
    
    protected observe(): void {
        this.controls = [
            this.$element.find('#churn_report_date_1'),
            this.$element.find('#churn_report_date_2')
        ];

        this.$element.find('.tn-tn_core-component-input-select-select').each((i: any, select: any) => {
            this.controls.push($(select));
        });


        this.$element.find('input[name="breakdown"]').on('change', this.triggerReload.bind(this));

        this.observeControls();
    }

    protected getReloadData(): ReloadData {
        let data: ReloadData = super.getReloadData();
        data.reportkey = this.$element.data('reportkey');
        this.$element.find('input[name="breakdown"]:checked').each(function() {
            data.breakdown = $(this).val();
        });
        return data;
    }
}