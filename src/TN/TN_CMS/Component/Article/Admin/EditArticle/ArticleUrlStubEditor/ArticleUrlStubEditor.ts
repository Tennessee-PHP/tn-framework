import {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ArticleUrlStubEditor extends HTMLComponent {
    
    protected observe(): void {
        this.$element.find('input#url_stub').on('blur', this.onUrlStubBlur.bind(this));
    }

    protected onUrlStubBlur(): void {
        this.$element.trigger('change', {
            urlStub: this.$element.find('input#url_stub').val()
        });
    }
    
}