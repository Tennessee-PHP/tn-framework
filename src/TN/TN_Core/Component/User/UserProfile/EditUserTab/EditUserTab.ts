import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';
import $, {Cash} from 'cash-dom';
import EditUserTabField from "./EditUserTabField";

export default class EditUserTab extends HTMLComponent {
    protected observe(): void {
        new EditUserTabField(this.$element.find('#edit_email_form'));
        new EditUserTabField(this.$element.find('#edit_name_form'));
        new EditUserTabField(this.$element.find('#edit_password_form'));
        new EditUserTabField(this.$element.find('#edit_username_form'));
        new EditUserTabField(this.$element.find('#user_active_change_form'));
        new EditUserTabField(this.$element.find('#merge_user_form'));
        this.$element.find('#merge_user_form, #user_active_change_form').on('success', () => window.location.reload());
    }

}