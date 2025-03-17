import Toast, {ToastOptions} from "./Toast";
import _ from "lodash";

export default class ErrorToast extends Toast {
    constructor(message: string, options: ToastOptions = {}) {
        super(_.assign({
            autohide: false,
            classes: ['text-white', 'bg-danger'],
            title: '<i class="bi bi-exclamation-circle-fill"></i> Something went wrong...',
            message: message
        }, options || {}));
    }
}