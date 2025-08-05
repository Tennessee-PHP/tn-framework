import $, {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ListSubscriptions extends HTMLComponent {

    protected updateUrlQueryOnReload: boolean = true;
    private $planSelect: Cash;
    private $billingCycleSelect: Cash;

    protected observe(): void {
        this.$planSelect = this.$element.find('.tn-tn_core-component-input-select-planselect-planselect');
        this.$planSelect.on('change', this.onPlanSelectChange.bind(this));
        
        this.$billingCycleSelect = this.$element.find('.tn-tn_core-component-input-select-billingcycleselect-billingcycleselect');
        this.$billingCycleSelect.on('change', this.onBillingCycleSelectChange.bind(this));

        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination'),
            this.$planSelect,
            this.$billingCycleSelect
        ];

        this.observeControls();
    }

    protected onPlanSelectChange(): void {
        this.$planSelect.data('value', this.$planSelect.val());
    }

    protected onBillingCycleSelectChange(): void {
        this.$billingCycleSelect.data('value', this.$billingCycleSelect.val());
    }
} 