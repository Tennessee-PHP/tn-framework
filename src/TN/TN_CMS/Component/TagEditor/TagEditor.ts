import _ from 'lodash';
import HTMLComponent, {ReloadData} from '@tn/TN_Core/Component/HTMLComponent';
import $, {Cash} from "cash-dom";
import axios from "axios";
import {Dropdown} from "bootstrap";

export default class TagEditor extends HTMLComponent {
    private $newTagInput: Cash;
    private tags: any[];
    private $tags: Cash;
    private $exampleTag: Cash;
    private dropdown: Dropdown;

    protected observe(): void {
        this.$newTagInput = this.$element.find('input.new-tag');
        this.$newTagInput.on('keyup', this.onTagsInput.bind(this));
        this.$newTagInput.on('keyup', _.debounce(this.onNewTagChange.bind(this), 500));
        this.dropdown = new Dropdown(this.$element.find('.dropdown').get(0));

        this.tags = [];
        this.$tags = this.$element.find('.tags');
        this.$tags.on('click', this.onTagsClick.bind(this));
        _.each(this.$element.find('.tags .tag'), (tag) => {
            this.tags.push({
                text: $(tag).text().trim(),
                primary: $(tag).find('.add-primary.d-none').length > 0
            });
        });

        this.onUpdate(false);

        this.$exampleTag = this.$element.find('.example-tag-container .tag');
    }

    protected onNewTagChange(e: any) {
        let tag = this.$newTagInput.val();
        if (tag.length > 2) {
            // @ts-ignore
            axios.get(TN.BASE_URL + 'tags/search?term=' + tag)
                .then(this.onAutoComplete.bind(this));
        }
    }

    protected onAutoComplete(response: any) {
        let tags = response.data;
        let newOptions = '';
        _.each(tags, (tag: any) => {
            newOptions += '<li><a class="dropdown-item" data-tag="' + tag.value + '">' + tag.label + '</a></li>';
        });
        let $autoCompleteMenu = this.$element.find('#tag_autocomplete_menu');
        $autoCompleteMenu.html(newOptions);
        $autoCompleteMenu.find('.dropdown-item').on('click', this.onDropdownItemClick.bind(this));
        this.dropdown.show();
    }

    protected onTagsClick(e: any): void {
        let $target = $(e.target);
        e.preventDefault();
        if ($target.hasClass('remove-tag') || $target.parents('.remove-tag').length) {
            this.removeTag($target.parents('.tag'));
            return;
        }
        if ($target.hasClass('remove-primary') || $target.parents('.remove-primary').length) {
            this.setTagPrimary($target.parents('.tag'), false);
            return;
        }
        if ($target.hasClass('add-primary') || $target.parents('.add-primary').length) {
            this.setTagPrimary($target.parents('.tag'), true);
            return;
        }
        this.$newTagInput.get(0).focus();
    }

    protected setNewTagInputWidth(): void {
        this.$newTagInput.width(((this.$newTagInput.val().length + 4) * 8));
    }

    protected onTagsInput(event: any): void {
        this.setNewTagInputWidth();
        if (event.keyCode === 13 || event.keyCode === 188) {
            this.addTag();
        }
    }

    protected onDropdownItemClick(event: Event): void {
        event.preventDefault();
        // @ts-ignore
        let $target = $(event.currentTarget);
        let tag = $target.data('tag');
        _.delay(() => {
            this.setNewTagInputWidth();
            this.addTag();
        }, 10);
        this.dropdown.hide();
    }

    protected hasTagText(text: any): boolean {
        let match = false;
        _.each(this.tags, (tag) => {
            if (tag.text === text) {
                match = true;
                return false;
            }
            return true;
        });
        return match;
    }

    protected addTag(): void {

        // @ts-ignore
        let text = this.$newTagInput.val().trim();
        if (text.charAt(text.length - 1) === ',') {
            text = text.substring(0, text.length - 1);
        }
        this.$newTagInput.val('');
        this.setNewTagInputWidth();

        // let's make sure we don't have exactly this tag already
        if (this.hasTagText(text)) {
            return;
        }

        let newTag = this.$exampleTag.clone();
        newTag.find('span.text').text(text);
        newTag.insertBefore(this.$newTagInput);

        this.tags.push({
            text: text,
            primary: false
        });

        this.onUpdate();
    }

    protected removeTag($tag: Cash): void {
        let text = $tag.find('span.text').text();
        $tag.remove();
        let newTags: any[] = [];
        _.each(this.tags, (tag) => {
            if (tag.text.toLowerCase() !== text.toLowerCase()) {
                newTags.push(tag);
            }
        });
        this.tags = newTags;
        this.onUpdate();
    }

    protected setTagPrimary($tag: Cash, primary: any): void {

        let text = $tag.find('span.text').text();

        if (primary) {
            $tag.find('.remove-primary').removeClass('d-none');
            $tag.find('.add-primary').addClass('d-none');
        } else {
            $tag.find('.remove-primary').addClass('d-none');
            $tag.find('.add-primary').removeClass('d-none');
        }

        _.each(this.tags, (tag) => {
            if (tag.text.toLowerCase() === text.toLowerCase()) {
                tag.primary = primary;
            }
        });

        this.onUpdate();

    }

    protected onUpdate(allowTrigger: boolean = true): void {
        // a parent always needs to pick this event up and handle it. the php class is ready to go with the actual editing.
        this.$element.data('tags', JSON.stringify(this.tags));
        if (allowTrigger) {
            this.$element.trigger('change', [this.tags]);
        }
    }
}