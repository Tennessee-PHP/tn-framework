import Toast, {ToastOptions} from "./Toast";
import _ from "lodash";

export default class SuccessToast extends Toast {
    constructor(message: string, options: ToastOptions = {}) {
        console.log(message);
        super(_.assign({
            autohide: true,
            delay: 5000,
            classes: ['text-white', 'bg-success'],
            title: '<i class="bi bi-check-circle-fill"></i> Success!',
            message: message
        }, options || {}));
    }
}