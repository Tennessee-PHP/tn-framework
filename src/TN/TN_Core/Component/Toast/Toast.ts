import $, {Cash} from 'cash-dom';
import {Toast as BootstrapToast} from 'bootstrap';
import _ from "lodash";

type ToastOptions = {
    delay?: number;
    classes?: string[];
    autohide?: boolean;
    message?: string;
    title?: string;
}

class Toast {
    protected options: ToastOptions;
    private $element: Cash;
    private toast: any;

    constructor(options: ToastOptions) {

        const defaults: ToastOptions = {
            delay: 2000,
            autohide: true,
            classes: [],
            message: '',
            title: ''
        };
        this.options = _.defaults(options, defaults);
        this.showToast();
    }

    showToast() {
        const $errorToastsContainer: Cash = $('#toasts_container');
        this.$element = $errorToastsContainer.find('.sample-toast').clone().removeClass('sample-toast');
        this.$element.find('.toast-body').html(this.options.message);
        this.$element.find('.toast-header .me-auto').html(this.options.title);
        _.each(this.options.classes, (cls) => {
            this.$element.addClass(cls);
        });
        this.$element.appendTo($errorToastsContainer);
        this.toast = new BootstrapToast(this.$element.get()[0], {
            delay: this.options.delay,
            autohide: this.options.autohide
        });

        this.$element.on('hidden.bs.toast', this.onToastHidden.bind(this));
        this.toast.show();
    }

    onToastHidden() {
        this.toast.dispose();
        this.$element.remove();
    }
}

export default Toast;
export {ToastOptions};