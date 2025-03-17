import $, {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class ArticleTitleEditor extends HTMLComponent {
    private $titleField: Cash;
    private $descriptionField: Cash;
    private $publishedTsField: Cash;
    private $authorSelect: Cash;
    private $avatar: Cash;
    
    protected observe(): void {
        this.$titleField = this.$element.find('input.editable-input-title').on('change', this.onTitleChange.bind(this));
        this.$descriptionField = this.$element.find('textarea.editable-input-summary').on('change', this.onDescriptionChange.bind(this));
        this.$publishedTsField = this.$element.find('input[type=datetime-local]').on('blur', this.onPublishedTsBlur.bind(this));
        this.$authorSelect = this.$element.find('#staffer_select').on('change', this.onAuthorIdChange.bind(this));
        this.$avatar = this.$element.find('img.staff-pic');
    }

    protected onTitleChange() {
        this.$element.trigger('change', {
            title: this.$titleField.val(),
            success: this.onTitleChangeSaved.bind(this)
        });
    }

    protected onTitleChangeSaved() {
        $('.tn-tn_cms-component-article-admin-editarticle-articleurlstubeditor-articleurlstubeditor').trigger('reload');
    }

    protected onDescriptionChange() {
        this.$element.trigger('change', {
            description: this.$descriptionField.val()
        });
    }

    protected onPublishedTsBlur() {
        this.$element.trigger('change', {
            publishedTs: this.$publishedTsField.val()
        });
    }

    protected onAuthorIdChange() {
        // get the currently selected option in the author select menu and change this.$avatar to have its avatarUrl as its src attribute
        let avatarUrl = this.$authorSelect.find('option:checked').data('avatarurl');
        this.$avatar.attr('src', avatarUrl);
        let authorId = this.$authorSelect.val();
        this.$element.trigger('change', {
            authorId: authorId
        });
    }
    
}