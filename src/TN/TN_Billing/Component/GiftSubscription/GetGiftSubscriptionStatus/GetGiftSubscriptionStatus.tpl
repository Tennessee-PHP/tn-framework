<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card">
                <h2 class="card-header">Plan: {$plan->name}</h2>
                <div class="card-body">
                    <p>Billing Cycle: <b>{$giftSubscription->billingCycleKey}</b></p>
                    <p>Plan Duration: <b>{$giftSubscription->duration}</b></p>
                    <p>Purchased on: <b>{$giftSubscription->createdTs|date_format:'%B %d, %Y'}</b></p>
                    <p>Order ID: <b>{$giftSubscription->id}</b></p>
                    <p>Sent to: <b>{$giftSubscription->recipientEmail}</b></p>

                    <div class="form-wrapper">
                        <form action="{path route='TN_Billing:GiftSubscription:submitStatus' key=$giftSubscription->key}">
                            {if !$giftSubscription->claimed}
                                <div class="alert-warning alert p-2">
                                    <div>Gift Status: <b>Pending</b></div>
                                </div>
                                <div class="row">
                                    <input type="hidden" value="{$giftSubscription->key}" name="key">
                                    <div class="col-12 d-flex justify-content-center py-3">
                                        <input type="submit" class="btn btn-primary" value="Remind recipient to redeem">
                                    </div>
                                </div>
                            {else}
                                <div class="alert-success p-2">
                                    <div>Gift Status: <b>Claimed</b></div>
                                </div>
                            {/if}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>