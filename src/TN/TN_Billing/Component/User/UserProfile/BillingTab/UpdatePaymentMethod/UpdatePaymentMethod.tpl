{* UpdatePaymentMethod template - Braintree hosted fields integration *}
<div class="alert alert-danger" style="display: none;"></div>

<form id="payment-form" data-update-payment-url="{path route="TN_Billing:UserProfile:updatePaymentMethod" userId=$user->id}">
        <input type="hidden" name="processpayment" value="{if isset($braintreeOverduePayment)}1{else}0{/if}">
        
        <div class="form-group mb-3">
            <label for="cardholder_name" class="form-label">Cardholder Name</label>
            <div id="cardholder_name" class="form-control"></div>
            <div class="invalid-feedback">Please enter a valid cardholder name</div>
        </div>

        <div class="form-group mb-3">
            <label for="card_number" class="form-label">Card Number</label>
            <div id="card_number" class="form-control"></div>
            <div class="invalid-feedback">Please enter a valid card number</div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="expiration_date" class="form-label">Expiration Date</label>
                    <div id="expiration_date" class="form-control"></div>
                    <div class="invalid-feedback">Please enter a valid expiration date</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="cvv" class="form-label">CVV</label>
                    <div id="cvv" class="form-control"></div>
                    <div class="invalid-feedback">Please enter a valid CVV</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <input type="submit" class="btn btn-primary" value="Update Payment Method" disabled>
            <div class="loading ms-2" style="display: none;">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </form> 