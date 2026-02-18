import $, {Element} from 'cash-dom';
import axios, { AxiosError } from 'axios';
import LiveReloadResource from "./LiveReloadResource";
import {IComponentFactory} from '../../IComponentFactory';
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class Page {

    static componentFactory: IComponentFactory;

    static setComponentFactory(factory: IComponentFactory): void {
        Page.componentFactory = factory;
    }

    constructor() {
        $('.tnc-component').each((i: number, element: Element) => {
            Page.componentFactory.createComponent($(element));
        });
        $('link').each((i: number, element: Element) => {
            if ($(element).data('live-reload-resource')) {
                new LiveReloadResource($(element));
            }
        });

        this.observeRevokeSessionsForm();
        this.observe();
    }

    /**
     * Document-level delegation for admin "Revoke All Sessions" form so it always submits via AJAX and shows a toast.
     * Uses capture phase so this handler runs before any other submit handlers (e.g. on the form) and can prevent the default POST/redirect.
     */
    private observeRevokeSessionsForm(): void {
        document.addEventListener(
            'submit',
            (e: Event): void => {
                const form = e.target as HTMLFormElement | null;
                if (!form?.hasAttribute?.('data-revoke-sessions-ajax')) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                const url = form.getAttribute('action');
                if (!url) {
                    return;
                }
                const button = form.querySelector<HTMLButtonElement>('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                }
                axios
                    .post(url, {}, { withCredentials: true })
                    .then((): void => {
                        new SuccessToast('All sessions revoked for this user.');
                    })
                    .catch((error: AxiosError): void => {
                        const message =
                            (error.response?.data as { message?: string })?.message ||
                            (error.response?.status === 403 ? 'You do not have permission to revoke sessions.' : 'Failed to revoke sessions.');
                        new ErrorToast(message);
                    })
                    .finally((): void => {
                        if (button) {
                            button.disabled = false;
                        }
                    });
            },
            true
        );
    }

    observe(): void {

    }

    updated(): void {

    }
}