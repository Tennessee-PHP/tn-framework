import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import $, {Cash} from 'cash-dom';
import EditUserTabField from "./EditUserTabField";
import SuccessToast from '@tn/TN_Core/Component/Toast/SuccessToast';
import _ from 'lodash';

export default class EditUserTab extends HTMLComponent {
    protected observe(): void {
        new EditUserTabField(this.$element.find('#edit_email_form'));
        new EditUserTabField(this.$element.find('#edit_name_form'));
        new EditUserTabField(this.$element.find('#edit_password_form'));
        new EditUserTabField(this.$element.find('#edit_username_form'));
        new EditUserTabField(this.$element.find('#user_active_change_form'));
        new EditUserTabField(this.$element.find('#merge_user_form'));
        this.$element.find('#merge_user_form, #user_active_change_form').on('success', () => {
            new SuccessToast('Success! Reloading Page...');
            _.delay(() => window.location.reload(), 2000);
        });
        this.$element.find('#generate_password').on('click', () => {
            const newPassword = this.generatePassword();
            this.$element.find('#field_password, #field_password_repeat').val(newPassword);
            new SuccessToast(`New password generated - remember to save it in the form above: ${newPassword}`);
        });
    }

    private generatePassword(): string {
        const length = 12;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let password = "";
        for (let i = 0, n = charset.length; i < length; ++i) {
            password += charset.charAt(Math.floor(Math.random() * n));
        }
        return password;
    }
}