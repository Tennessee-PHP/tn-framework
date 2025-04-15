import $, {Cash} from 'cash-dom';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";

export default class ArticleStatusEditor extends HTMLComponent {
    private status: string;
    private $publishArticleModal: Cash;
    private $currentDatetimeSpans: Cash;
    private $datetimeString: string;
    private $datetime: Date;
    private $formattedDateTime: string;
    private setpublishtstonow: number;
    
    protected observe(): void {
        this.$element.find('button.draft-btn').on('click', this.onDraftBtn.bind(this));
        this.$element.find('button.editor-btn').on('click', this.onEditorBtn.bind(this));
        this.$element.find('button.publish-btn').on('click', this.onPublishedBtn.bind(this));
        this.$element.find('button.template-btn').on('click', this.onTemplateBtn.bind(this));
        this.status = '';
        this.setpublishtstonow = 0;
    }

    protected onDraftBtn() {
        this.status = 'draft';
        this.reload();
    }

    protected onEditorBtn() {
        this.status = 'editor';
        this.reload();
    }

    protected onPublishedBtn() {
        this.$publishArticleModal = $("#publisharticlemodal");
        this.$currentDatetimeSpans = this.$publishArticleModal.find("span.current-datetime");
        this.$datetimeString = <string>$('#publishedTs').val();
        this.$datetime = new Date(this.$datetimeString);
        this.$formattedDateTime = this.$datetime.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            second: 'numeric',
            hour12: true
        });
        this.$currentDatetimeSpans.text(this.$formattedDateTime);

        this.$publishArticleModal.find("button.publish-now-btn").on('click', this.onPublishNowBtn.bind(this));
        this.$publishArticleModal.find("button.publish-ts-btn").on('click', this.onPublishTsBtn.bind(this));
    }

    protected onPublishNowBtn() {
        this.status = 'publish';
        this.setpublishtstonow = 1;
        this.reload();
    }

    protected onPublishTsBtn() {
        this.status = 'publish';
        this.reload();
    }

    protected onTemplateBtn() {
        this.status = 'template';
        this.reload();
    }

    protected getReloadData(): ReloadData {
        let data: ReloadData = super.getReloadData();
        data.status = this.status;
        data.setpublishtstonow = this.setpublishtstonow;
        data.articleId = $('.tn-tn_cms-component-article-admin-editarticle-editarticle').data('articleid');
        return data;
    }
    
}