import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import axios, { AxiosError } from 'axios';
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import ErrorToast from '@tn/TN_Core/Component/Toast/ErrorToast';

export default class UserProfile extends HTMLComponent {
    protected observe(): void {
        const $form = this.$element.find('[data-revoke-sessions-ajax]');
        if ($form.length === 0) {
            return;
        }
        $form.on('submit', (e: Event): void => {
            e.preventDefault();
            const url = $form.attr('action');
            if (!url) {
                return;
            }
            const $button = $form.find('button[type="submit"]');
            $button.prop('disabled', true);
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
                    $button.prop('disabled', false);
                });
        });
    }
}
