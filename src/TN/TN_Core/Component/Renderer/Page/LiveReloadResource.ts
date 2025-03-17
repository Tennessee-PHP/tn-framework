import $, {Cash, Element} from 'cash-dom';
import axios from "axios";

export default class LiveReloadResource {
    private $element: Cash;
    private resourcePath: string;
    private checkUrl: string;
    private lastModified: number;
    private secondsBetweenChecks = 1;

    constructor($element: Cash) {
        this.$element = $element;
        this.resourcePath = $element.data('live-reload-resource');
        this.checkUrl = $element.data('live-reload-check-url');
        this.lastModified = $element.data('live-reload-last-modified');
        this.deferCheck();
    }

    private deferCheck(): void {
        setTimeout(() => {
            this.check();
        }, this.secondsBetweenChecks * 1000);
    }

    private check(): void {
        axios.get(this.checkUrl, {
            params: {
                resourcePath: this.resourcePath
            }
        })
            .then(this.checkSuccess.bind(this))
            .catch(this.checkError.bind(this));
    }

    private checkSuccess(response: any): void {
        this.deferCheck();
        const responseLastModified: number = parseInt(response.data, 10);
        if (responseLastModified === this.lastModified) {
            return;
        }
        this.lastModified = responseLastModified;

        let href: string = this.$element.attr('href');
        const queryIndex: number = href.indexOf('?');
        if (queryIndex !== -1) {
            href = href.substring(0, queryIndex);
        }


        this.$element.attr('href', href + '?_cb=' + this.lastModified);
    }

    private checkError(error: any): void {
        this.deferCheck();
    }
}