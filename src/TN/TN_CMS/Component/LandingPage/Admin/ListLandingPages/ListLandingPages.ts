import HTMLComponent from "../../../../../TN_Core/Component/HTMLComponent";
import $ from 'cash-dom';

export default class ListLandingPages extends HTMLComponent {
    protected observe(): void {
        this.controls = [
            this.$element.find('.tn-tn_core-component-pagination-pagination')
        ];
        this.observeControls();

        const deleteModalElement = this.$element.find('#deletelandingpagemodal').get(0);
        if (deleteModalElement) {
            deleteModalElement.addEventListener('show.bs.modal', this.handleDeleteModalShow.bind(this));
        }
    }

    /**
     * Handles the 'show.bs.modal' event for the delete confirmation modal.
     * Updates the modal title with the landing page title from the triggering button.
     */
    private handleDeleteModalShow(event: Event): void {
        // @ts-ignore - Bootstrap's event type might not be standard
        const button = event.relatedTarget; // Button that triggered the modal
        if (button) {
            const title = $(button).data('landingpagetitle');
            // Use this.$element to find the modal within the component's scope
            const modalTitleSpan = this.$element.find('#deletelandingpagemodal .landingpage-title');
            modalTitleSpan.text(title || ''); // Set the text, default to empty string if title is null/undefined
            const landingPageId = $(button).data('landingpageid');
            const deleteConfirmButton = this.$element.find('#deletelandingpagemodal .delete-confirm');
            // @ts-ignore
            deleteConfirmButton.attr('href', TN.BASE_URL + 'staff/landing-pages?deleteId=' + landingPageId);
        }
    }
}