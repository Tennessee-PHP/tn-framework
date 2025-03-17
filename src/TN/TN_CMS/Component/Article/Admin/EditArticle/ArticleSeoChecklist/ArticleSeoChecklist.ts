import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import $, {Cash} from "cash-dom";

export default class ArticleSeoChecklist extends HTMLComponent {
    private $seoKeyword: any;
    private $seoKeywordInvalidFeedback: Cash;

    protected observe(): void {
        this.$seoKeyword = this.$element.find('input#main_keyword').on('blur', this.onSeoPrimaryKeywordBlur.bind(this));
        this.$seoKeywordInvalidFeedback = this.$element.find('.is-invalid');
        this.$seoKeywordInvalidFeedback = this.$element.find('.is-invalid');


        this.$element.on('contentchange', this.onArticleContentChange.bind(this));

        let $articleTitleEditor = $('.tn-tn_cms-component-article-admin-editarticle-articletitleeditor-articletitleeditor');
        $articleTitleEditor.on('change', this.onArticleTitleEditorChange.bind(this));
        this.onArticleTitleEditorChange(null, {
            title: $articleTitleEditor.find('input.editable-input-title').val(),
            description: $articleTitleEditor.find('textarea.editable-input-summary').val()
        });

        let $articleUrlStubEditor = $('.tn-tn_cms-component-article-admin-editarticle-articleurlstubeditor-articleurlstubeditor');
        $articleUrlStubEditor.on('change', this.onArticleUrlStubChange.bind(this));
        this.onArticleUrlStubChange(null, {
            urlStub: $articleUrlStubEditor.find('input').val()
        });

    }

    protected onArticleTitleEditorChange(e: any, data: any) {
        if (!data) {
            return;
        }

        const mainKeyword = this.$seoKeyword.val();

        if (data.title) {
            const title = data.title;
            let startsWithTitle = title.toLowerCase().startsWith(mainKeyword.toLowerCase());
            if (mainKeyword.trim() === '') {
                startsWithTitle = false;
            }
            this.updateChecklistItemStatus('keyword_start_title', startsWithTitle);
        }
        if (data.description) {
            let startsWithDescription = data.description.toLowerCase().startsWith(mainKeyword.toLowerCase());
            if (mainKeyword.trim() === '') {
                startsWithDescription = false;
            }
            this.updateChecklistItemStatus('keyword_start_meta', startsWithDescription);
        }
    }

    protected onArticleUrlStubChange(e: any, data: any) {

        if (!data) {
            return;
        }

        if (!data.urlStub) {
            return;
        }

        const url = data.urlStub;
        const mainKeyword = this.$seoKeyword.val();
        const keywordRegex = new RegExp(`\\b${mainKeyword}\\b`, 'i');
        let includesKeyword = keywordRegex.test(url);
        if (mainKeyword.trim() === '') {
            includesKeyword = false;
        }
        this.updateChecklistItemStatus('keyword_in_url', includesKeyword)
    }

    protected onArticleContentChange(e: any, data: any) {
        let content: string = data.content;
        this.checkKeywordDensity(content);
        this.checkHeaderDensity(content);
        this.checkInboundLink(content);
        this.checkOutboundLink(content);
        this.checkAltTagKeyword(content);
    }

    protected onSeoPrimaryKeywordBlur() {

        let spacedKeywords = this.$seoKeyword.val();
        let hyphenedKeywords = spacedKeywords.replace(/ /g, "-");

        let $articleTitleEditor = $('.tn-tn_cms-component-article-admin-editarticle-articletitleeditor-articletitleeditor');
        this.onArticleTitleEditorChange(null, {
            title: $articleTitleEditor.find('input.editable-input-title').val(),
            description: $articleTitleEditor.find('textarea.editable-input-summary').val()
        });

        let $articleUrlStubEditor = $('.tn-tn_cms-component-article-admin-editarticle-articleurlstubeditor-articleurlstubeditor');
        this.onArticleUrlStubChange(null, {
            urlStub: $articleUrlStubEditor.find('input').val()
        });

        this.$element.trigger('change', {
            primarySeoKeyword: hyphenedKeywords,
            error: this.onSeoPrimaryKeywordEditError.bind(this),
            success: this.onSeoPrimaryKeywordEditSuccess.bind(this)
        });

    }

    protected onSeoPrimaryKeywordEditSuccess(data: any) {

        $('.tn-tn_cms-component-article-admin-editarticle-articleurlstubeditor-articleurlstubeditor').trigger('reload');

        // remove bootstrap error on field. remove is-invalid class, hide the invalid-feedback div.
        this.$seoKeywordInvalidFeedback.removeClass('is-invalid');
        this.$seoKeywordInvalidFeedback.removeClass('invalid-feedback');

        this.$seoKeywordInvalidFeedback.addClass('d-none');

    }

    protected  onSeoPrimaryKeywordEditError(response: any) {
        let errorMsg = response.responseText;
        // this.$seoKeyword <= that is the input element

        // add the errorMsg to the invalid-feedback div and show it.
        this.$seoKeywordInvalidFeedback.text(errorMsg);
        this.$seoKeywordInvalidFeedback.removeClass('d-none');
        this.$seoKeywordInvalidFeedback.addClass('invalid-feedback');
    }

    protected updateChecklistItemStatus(itemId: any, passes: any) {
        let itemElement = document.getElementById(itemId);
        let statusElement = document.getElementById(`${itemId}_status`);

        if (passes) {
            itemElement.classList.add('text-decoration-line-through', 'text-muted')
            statusElement.innerHTML = '<i class="bi bi-check text-success"></i>';
        } else {
            itemElement.classList.remove('text-decoration-line-through', 'text-muted')
            statusElement.innerHTML = '<i class="bi bi-x text-danger"></i>';

        }
    }

    protected checkKeywordDensity(content: any) {

        let articleText = '';

        $(content).each(function() {
            articleText += $(this).text() + ' ';
        });

        let wordCount = articleText.trim().split(/\s+/).length;
        let mainKeyword = this.$seoKeyword.val();

        let regex = new RegExp("\\b" + mainKeyword + "\\b", "gi");
        let keywordCount = (articleText.match(regex) || []).length;
        let keywordDensity = keywordCount / (wordCount / 100);
        let passes = keywordDensity >= 1;

        if (mainKeyword.trim() === '') {
            passes = false;
        }

        this.updateChecklistItemStatus('keyword_density', passes)
    }

    protected checkHeaderDensity(content: any) {
        const mainKeyword = this.$seoKeyword.val();

        const headers = $('#editor_ifr').contents().find('h1,h2,h3,h4,h5,h6');
        const totalHeaders = headers.length;

        let headersWithKeyword = 0;
        let totalKeywordCount = 0;

        headers.each((i: number, element: any) => {
            const headerText = $(element).text();

            const keywordCountInHeader = (headerText.match(new RegExp(mainKeyword, 'gi')) || []).length;
            totalKeywordCount += keywordCountInHeader;
            headersWithKeyword += keywordCountInHeader > 0 ? 1 : 0;
        });

        let keywordDensityInHeaders = totalKeywordCount / (totalHeaders / 5);
        let passes = headersWithKeyword > 0 && keywordDensityInHeaders >= 1;

        if (mainKeyword.trim() === '') {
            passes = false;
        }

        this.updateChecklistItemStatus('keyword_header_density', passes);
    }

    protected checkInboundLink(content: any) {
        const hasInboundLink = $(content).find('a[href*="domain.com"]').length > 0;
        this.updateChecklistItemStatus('inbound_link', hasInboundLink);
    }

    protected checkOutboundLink(content: any) {
        let hasOutboundLink = false;
        $(content).find('a[href]').each((i: number, element: any) => {
            if (!$(element).attr('href').includes('domain.com')) {
                hasOutboundLink = true;
            }
        });
        this.updateChecklistItemStatus('outbound_link', hasOutboundLink);
    }

    protected checkAltTagKeyword(content: any) {
        const mainKeyword = this.$seoKeyword.val();

        const images = $(content).find('img');

        let keywordInAlt = false;

        images.each(function () {
            const alt = $(this).attr('alt');

            if (alt && alt.toLowerCase().includes(mainKeyword.toLowerCase())) {
                keywordInAlt = true;
                return false;
            }
        });

        if (mainKeyword.trim() === '') {
            keywordInAlt = false;
        }

        this.updateChecklistItemStatus('keyword_in_alt_tag', keywordInAlt);
    }
}