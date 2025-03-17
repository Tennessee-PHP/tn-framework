import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class Select extends HTMLComponent {

    observe() {
        //this.$element.on('change', this.onChange.bind(this));
        /*if (this.$element.attr('multiple') === 'multiple') {
            this.$element.multiselect({
                includeSelectAllOption: true,
                numberDisplayed: 1,
                allSelectedText: 'All',
                widthSynchronizationMode: 'ifPopupIsSmaller',
                onChange: this.onChange.bind(this)
            });
        }*/
    }
}