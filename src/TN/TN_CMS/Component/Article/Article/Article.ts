import $, {Cash} from 'cash-dom';
import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import _ from "lodash";

export default class Article extends HTMLComponent {
    private $articleContent: Cash;
    private _attemptLoadTwitterWidgetsTimeout: number;
    
    protected observe(): void {
        this.$articleContent = this.$element.find('.article-content');
        this.activateVisibleEmbeds();
        $(window).on('scroll', this.activateVisibleEmbeds.bind(this));
        $(window).on('resize', this.activateVisibleEmbeds.bind(this));
    }

    activateVisibleEmbeds() {
        let didActivate: boolean = false;
        this.$articleContent.find('.twitter-tweet-toload').each((i, el) => {
            let $el = $(el);
            if (this.isScrolledIntoView($el)) {
                this.activateEmbed($el);
                didActivate = true;
            }
        });
        if (!didActivate) {
            return;
        }
        this.attemptLoadTwitterWidgets();
    }

    attemptLoadTwitterWidgets() {
        // @ts-ignore
        if (typeof(twttr) === 'undefined' || typeof(twttr.widgets) === 'undefined') {
            clearTimeout(this._attemptLoadTwitterWidgetsTimeout);
            this._attemptLoadTwitterWidgetsTimeout = _.delay(this.attemptLoadTwitterWidgets.bind(this), 1000);
            return;
        }
        // @ts-ignore
        twttr.widgets.load();
        this.activateVisibleEmbeds();
    }

    isScrolledIntoView($el: any) {
        var docViewTop = window.scrollY;
        var docViewBottom = docViewTop + $(window).height();

        var elemTop = $el.offset().top;
        var elemBottom = elemTop + $el.height();

        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    }

    activateEmbed($el: any) {
        $el.removeClass('twitter-tweet-toload');
        $el.addClass('twitter-tweet');
    }
    
}