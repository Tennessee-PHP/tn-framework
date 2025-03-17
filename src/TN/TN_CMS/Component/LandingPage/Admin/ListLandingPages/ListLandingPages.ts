import HTMLComponent from "../../../../../TN_Core/Component/HTMLComponent";

export default class ListLandingPages extends HTMLComponent {
    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination')
        ];
        this.observeControls();
    }
}