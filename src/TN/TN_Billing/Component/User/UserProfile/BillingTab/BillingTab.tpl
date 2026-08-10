<div class="{$classAttribute}" id="{$idAttribute}">
    {if $activeSubscription}
        <div class="d-flex justify-content-center container mb-3">
            <div class="alert alert-success border text-center w-100 px-5 py-2">
                You are currently subscribed to the <b>{$activePlan}</b> plan.

                {if $activeSubscription->endTs} This plan ends on {$activeSubscription->endTs|date_format:"%B %e, %Y"}
                    {if $activeSubscription->gatewayKey === 'braintree'}, and will not renew after this time - it is cancelled{/if}.
                {elseif $nextRenewalComplimentary && $activeSubscription->nextTransactionTs}
                    <div class="mt-2 mb-0">
                        <strong>Your next renewal on {$activeSubscription->nextTransactionTs|date_format:"%B %e, %Y"} is free.</strong>
                    </div>
                {elseif $activeSubscription->nextTransactionTs} You will next be billed on {$activeSubscription->nextTransactionTs|date_format:"%B %e, %Y"}.
                {/if}
                {*{if $activeSubscription->endReason}
                    {if array_key_exists($activeSubscription->endReason, $endReasonDescriptions)}
                        <p>{$endReasonDescriptions[$activeSubscription->endReason]}</p>
                    {/if}
                {/if}*}
                {if !$hasHighestPlan || $activeSubscription->billingCycleKey !== 'annually'}
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4 mb-1">
                        {if !$hasHighestPlan}
                            <a class="btn btn-lg btn-cta user-action-btn" href="{$BASE_URL}plans">Upgrade Plan</a>
                        {/if}
                        {if $activeSubscription->billingCycleKey !== 'annually'}
                            <a
                                    class="btn btn-lg btn-cta user-action-btn"
                                    href="{$BASE_URL}checkout/plan/{$activeSubscription->getPlan()->key}/annually"
                            >
                                Switch To Annual and Save ${$switchToAnnualSavings|number_format:2}
                            </a>
                        {/if}
                    </div>
                {/if}
                {if $canShowCancelButton}
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4 mb-4">
                        <button
                                class="btn btn-outline-danger user-action-btn"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#cancelplan_modal">
                            Turn Off Auto-Renew
                        </button>
                    </div>
                {/if}
                <a href="{$BASE_URL}your-subscription">Click here to learn how to get the most from your
                    subscription.</a>
            </div>
        </div>
        {if $activeSubscriptionIsBraintree}
            <div class="d-flex justify-content-center container mb-3">

                <div class="alert-{if $inGracePeriod || ($braintreeCustomer && empty($braintreeCustomer->getReadablePaymentMethod()))}danger{else}secondary{/if} border px-5 py-2">
                    {if $braintreeCustomer}
                        {if !empty($braintreeCustomer->getReadablePaymentMethod())}
                            Your payment method is set to {* just prints out the details of an previously vaulted payment from a braintree customer *}
                            {$braintreeCustomer->getReadablePaymentMethod()}
                            {if !empty($braintreeCustomer->accountName)}
                                account ({$braintreeCustomer->accountName})
                            {else}
                                card
                                {if $braintreeCustomer->cardExpiration}
                                    ({$braintreeCustomer->cardType}, expiring {$braintreeCustomer->cardExpiration})
                                {/if}
                            {/if}
                        {else}
                            We do not currently have a valid payment method for you. Please add one:
                        {/if}
                    {/if}
                    <div class="d-flex justify-content-center mt-4 mb-4">
                        <button
                                class="btn btn-outline-secondary smaller-btn"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#changepaymentmethod_modal">
                            Update Your Payment Method
                        </button>
                    </div>
                </div>
            </div>
        {/if}
    {else}
        <div class="d-flex justify-content-center container mb-3">
            <div class="alert-success border px-5 py-2">
                You are currently on a free <b>{$activePlan}</b> plan.

                <div class="d-flex justify-content-center mt-4 mb-1">
                    <a class="mx-2 btn lg-btn btn-cta" href="{$BASE_URL}plans">Upgrade Plan</a>
                </div>

            </div>
        </div>
    {/if}

    <h3>Purchase History for {$user->username}</h3>
    {if !empty($historicalSubscriptions)}
        {if $subscriptionsReorganized}
            <p class="alert alert-success">Subscriptions were successfully reorganized.</p>
        {/if}
        {if $observer->hasRole('sales-admin')}
            <div class="d-flex justify-content-end mb-3">

                <a class="btn mx-2 btn-primary"
                   href="{$BASE_URL}staff/users/user/{$user->id}/plans?reorganizesubscriptions=1">Organize
                    Subscriptions</a>
                <input type="button" class="btn btn-primary refund-btn mx-2" value="Process Refunds" disabled
                       data-bs-toggle="modal"
                       data-bs-target="#actionrefund_modal">

            </div>
        {/if}
        <div class="table-responsive form-wrapper">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">Payment System</th>
                    {if $observer->hasRole('sales-admin')}
                        <th scope="col">Payment System ID</th>
                    {/if}
                    <th scope="col">Payment Date</th>
                    <th scope="col">Payment Amount</th>
                    <th scope="col">Payment Successful</th>
                    <th scope="col">Plan/Product</th>
                    <th scope="col">Plan Duration</th>
                    <th scope="col">Refunded</th>
                    {if $observer->hasRole('sales-admin')}
                        <th scope="col">
                            <div class="d-flex justify-content-center">Refund</div>
                        </th>
                    {/if}
                </tr>
                </thead>
                <tbody>
                {foreach $historicalSubscriptions as $subscription}
                    {if $subscription->active}
                        <tr>
                            <td colspan="{if $observer->hasRole('sales-admin')}9{else}8{/if}">Payment System:
                                <b>{$subscription->gatewayKey}</b> /
                                Plan Level:
                                <b>{\TN\TN_Billing\Model\Subscription\Plan\Plan::getInstanceByKey($subscription->planKey)->name}</b>
                                / Plan Start: <b>{$subscription->startTs|date_format:"%B %e, %Y"}</b> /
                                Plan End:
                                <b>
                                    {if $subscription->endTs !== 0}
                                        {$subscription->endTs|date_format:"%B %e, %Y"}
                                    {else}
                                        -
                                    {/if}
                                </b>
                            </td>
                        </tr>
                        {foreach $subscription->transactions as $t}
                            <tr class="transaction-row">
                                <td>{$subscription->gatewayKey}</td>
                                {if $observer->hasRole('sales-admin')}
                                    <td>{if $t->braintreeId}{$t->braintreeId}{elseif $t->rotoPassId}{$t->rotoPassId}{elseif $t->appleId}{$t->appleId}{else}-{/if}</td>
                                {/if}
                                <td>{$t->ts|date_format:"%B %e, %Y"}</td>
                                <td>${$t->amount}</td>
                                <td class="bg-{if $t->success}success{else}danger{/if} text-white">{if $t->success}Yes{else}No{/if}</td>
                                <td>{TN\TN_Billing\Model\Subscription\Plan\Plan::getInstanceByKey($subscription->planKey)->name}</td>
                                <td>{$subscription->billingCycleKey}</td>
                                <td>{if $t->refunded}Yes{else}No{/if}</td>

                                {if $observer->hasRole('sales-admin')}
                                    {if $subscription->gatewayKey === 'braintree' && !$t->refunded}
                                        <td class="align-center"><input type="checkbox" name="{$subscription@key}"
                                                                        value="{$t->id}"
                                                                        class="refund-check"></td>
                                    {else}
                                        <td>n/a</td>
                                    {/if}
                                {/if}

                            </tr>
                        {/foreach}
                    {/if}
                {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <div class="container">
            <p>
                No transaction or refund data found for {$user->username}.
            </p>
        </div>
    {/if}
    <div
            class="d-flex justify-content-end  modal-div"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#actionrefund_modal">
        {if !empty($transactionHistory)}
            {if $observer->hasRole('sales-admin')}
                <input type="button" class="btn btn-primary refund-btn" value="Process Refunds" disabled>
            {/if}
        {/if}
    </div>
    <div class="form-loading" style="text-align:center; display:none;">
        <div class="spinner-border" role="status">
            <span class="sr-only"></span>
        </div>
    </div>

    {* theres tons of modals on this page! *}
    <div class="modal fade" id="actionrefund_modal" tabindex="-1" role="dialog" aria-labelledby="actionRefundTitle"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionRefundTitle">Reason for Refund</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <section id="User_Plans_Staffer_Refunds">
                    <form id="user_plans_staffer_refunds_form" action="{path route="TN_Billing:Refund:refundPayments" userId=$user->id}">
                        <input type="hidden" value="{$user->id}" name="id">
                        <div class="modal-body">
                            <label for="form-floating">Reason</label>
                            <div class="form-floating mb-3">
                                <select class="form-select" name="reason" id="refund_form_select"
                                        aria-label="Floating label select example">
                                    {foreach $refundReasons as $reason}
                                        <option value="{$reason@key}">{$reason}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <label for="form-floating">Additional Comments</label>
                            <div class="form-floating">
                                <textarea name="comment" id="floatingTextarea"></textarea>
                                <label for="floatingTextarea"></label>
                            </div>
                            <input type="checkbox" name="cancel" value="1" id="cancel_subscription" checked/><label
                                    for="cancel_subscription"> Cancel Subscription?</label>
                        </div>
                        <div class="modal-footer d-flex justify-content-center">
                            <button type="submit" id="refund_submit_btn" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changepaymentmethod_modal" tabindex="-1" role="dialog"
         aria-labelledby="changePaymentMethodModalTitle"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePaymentMethodModalTitle">Change Payment Method</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {* do we have a payment pending for this user? If so display clearly how much outstanding will be billed *}
                    <p>
                        {if $braintreeOverduePayment}
                            Your payment due on account of
                            <b>${$braintreeOverduePayment|number_format:2}</b>
                            will be processed
                            and your subscription will be renewed.
                        {/if}
                        We'll use this payment method for all renewals moving forwards.
                    </p>
                    {include file="TN_Billing/Component/User/UserProfile/BillingTab/UpdatePaymentMethod/UpdatePaymentMethod.tpl"}
                </div>
            </div>
        </div>
    </div>

    {if $canShowCancelButton}
    <div class="modal fade" id="cancelplan_modal" tabindex="-1" role="dialog" aria-labelledby="cancelPlanModalTitle"
         aria-hidden="true"
         data-abandon-url="{path route="TN_Billing:Subscription:abandonCancellationAttempt" userId=$user->id}"
         data-survey-url="{path route="TN_Billing:Subscription:saveCancellationSurvey" userId=$user->id}"
         data-accept-offer-url="{path route="TN_Billing:Subscription:acceptRetentionOffer" userId=$user->id}"
         data-cancel-url="{path route="TN_Billing:Subscription:cancelSubscription" userId=$user->id}"
         data-user-id="{$user->id}">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelPlanModalTitle">Turn Off Auto-Renew</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="cancellation-wizard-loading text-center py-4" style="display: none;">
                    <div class="spinner-border" role="status"><span class="visually-hidden">Loading…</span></div>
                </div>

                <div class="cancellation-wizard-step" data-step="survey">
                    <div class="modal-body">
                        {if $activeSubscription->nextTransactionTs}
                            <p class="mb-3">You'll keep access until <b>{$activeSubscription->nextTransactionTs|date_format:"%B %e, %Y"}</b>. You won't be charged again.</p>
                        {/if}
                        <p class="mb-3">Before you go, please tell us why you're cancelling. Your feedback helps us improve.</p>
                        <div class="mb-3">
                            <label for="cancellation_reason_select" class="form-label">Reason for cancelling <span class="text-danger">*</span></label>
                            <select class="form-select" id="cancellation_reason_select" required>
                                <option value="">Select a reason…</option>
                                {foreach $cancellationReasonOptions as $code => $label}
                                    <option value="{$code|escape}">{$label|escape}</option>
                                {/foreach}
                            </select>
                            <div class="invalid-feedback">Please select a reason.</div>
                        </div>
                        <div class="mb-3">
                            <label for="cancellation_comment" class="form-label">Additional comments (optional)</label>
                            <textarea class="form-control" id="cancellation_comment" rows="3"
                                      placeholder="Tell us more about your experience…"></textarea>
                            <div class="form-text" id="cancellation_other_hint" style="display: none;">Please share a few details when selecting Other.</div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" id="cancellation_survey_continue_btn">Continue</button>
                    </div>
                </div>

                <div class="cancellation-wizard-step" data-step="save" style="display: none;">
                    <div class="modal-body">
                        <p id="cancellation_reason_acknowledgement" class="mb-3"></p>
                        {if $scheduledDowngradeSummary}
                            <p class="alert alert-warning">
                                Turning off auto-renew will also cancel your scheduled change to
                                <b>{$scheduledDowngradeSummary.toPlanName}</b>.
                            </p>
                        {/if}
                        <div id="cancellation_offer_available">
                            <div class="alert alert-info border mb-3">
                                <p class="fw-semibold mb-0" id="cancellation_offer_label"></p>
                            </div>
                        </div>
                        <div id="cancellation_existing_discount" style="display: none;">
                            <div class="alert alert-info border mb-3">
                                <p class="fw-semibold mb-0" id="cancellation_existing_discount_label"></p>
                            </div>
                        </div>
                        <p class="mb-2">You might also like:</p>
                        <ul class="mb-4">
                            <li><a href="{$BASE_URL}your-subscription">Learn how to get the most from your subscription</a></li>
                            {if !$hasHighestPlan}
                                <li><a href="{$BASE_URL}plans">Upgrade Plan</a></li>
                            {/if}
                        </ul>
                        <button type="button" class="btn btn-lg btn-success w-100 mb-3" id="cancellation_accept_offer_btn">Accept Offer</button>
                        <button type="button" class="btn btn-lg btn-danger w-100" id="cancellation_decline_offer_btn">Turn Off Auto-Renew</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {/if}

</div>