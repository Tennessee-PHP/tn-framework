import $, {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";

export default class ArticleMetadataEditor extends HTMLComponent {
    private $weekBySeasonSelect: Cash;
    private $contentRequiredSelect: Cash;
    private $articleWeightSelect: Cash;

    protected observe(): void {
        this.$weekBySeasonSelect = this.$element.find('.tn-component-select-weekbyseasonselect-weekbyseasonselect');
        this.$contentRequiredSelect = this.$element.find('#content_required_select');
        this.$articleWeightSelect = this.$element.find('#article_weight_select');
        this.$weekBySeasonSelect.on('change', this.onWeekBySeasonSelectChange.bind(this));
        this.$contentRequiredSelect.on('change', this.onContentRequiredSelectChange.bind(this));
        this.$articleWeightSelect.on('change', this.onArticleWeightSelectChange.bind(this));
    }

    protected onWeekBySeasonSelectChange(): void {
        let value: string|string[] = this.$weekBySeasonSelect.val();
        let parts: string[] = typeof value === 'string' ? value.split('-') : value[0].split('-');
        let week: number = _.parseInt(parts[1]);
        let year: number = _.parseInt(parts[0]);
        this.$element.trigger('change', {
            week: week,
            year: year,
            success: this.onWeekBySeasonSelectSaved.bind(this)
        });
    }

    protected onWeekBySeasonSelectSaved(): void {
        $('.tn-tn_cms-component-article-admin-editarticle-articleurlstubeditor-articleurlstubeditor').trigger('reload');
    }

    protected onContentRequiredSelectChange(): void {
        this.$element.trigger('change', {
            contentRequired: this.$contentRequiredSelect.val()
        });
    }

    protected onArticleWeightSelectChange(): void {
        this.$element.trigger('change', {
            weight: this.$articleWeightSelect.val()
        });
    }

    protected onUrlStubBlur(): void {
        this.$element.trigger('change', {
            urlStub: this.$element.find('input#url_stub').val()
        });
    }

}