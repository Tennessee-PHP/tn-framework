import {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class UserSelect extends HTMLComponent {
    
    protected observe(): void {
        this.$element.on('change', this.onChange.bind(this));
    }

    protected onChange(): void {
        this.$element.data('value', this.$element.val());
    }
    
}