<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    <form action="{$BASE_URL}staff/sales/comp" id="staff_comp_form">
        <div class="form-group">
            <label for="email_input_field" class="form-label">Insert Email Addresses</label>
            <textarea name="emails" id="email_input_field" class="form-control"></textarea>
            <small id="enabled_help" class="form-text text-muted">Put email addresses in either comma seperated
                or
                newline seperated.</small>
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="check-label" for="plan_select">Plan</b></label>
                    <select class="form-control" name="plan" id="plan_select">
                        {foreach $plans as $plan}
                            {if $plan->paid}
                                <option value="{$plan->key}">{$plan->name}</option>
                            {/if}
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="check-label" for="duration_select">Duration</b></label>
                    <select class="form-control" name="duration" id="duration_select">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="100">Lifetime</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="check-label" for="billing_select">Billing Cycle</label>
                    <select class="form-control" name="billing" id="billing_select">
                        {foreach $billingCycles as $cycle}
                            <option value="{$cycle->key}">{$cycle->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="check-label" for="comment_select">Comment</label>
                    <select class="form-control" name="comment" id="comment_select">
                        {foreach $compReasons as $comment}
                            <option value="{$comment}">{$comment}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="text-center mb-3">
                <input type="submit" class="btn btn-primary" value="Send Complimentary Plans"/>

                <div id="edit-loading" style="text-align:center; display:none;">
                    <div class="spinner-border" role="status">
                        <span class="sr-only"></span>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Recipient Email</th>
                <th scope="col">Claimed</th>
                <th scope="col">Claimed By</th>
                <th scope="col">Plan</th>
                <th scope="col">Duration Type</th>
                <th scope="col">Duration Length</th>
                <th scope="col">Reason Gifted</th>
                <th scope="col">Creation Time</th>
            </tr>
            </thead>
            <tbody class="table-wrapper">
            {foreach $giftSubscriptions as $giftSubscription}
                <tr>
                    <td>{$giftSubscription->recipientEmail}</td>
                    <td class="bg-{if $giftSubscription->claimed}success{else}danger{/if} text-white">{if $giftSubscription->claimed}Yes{else}No{/if}</td>
                    <td>{if isset($giftSubscription->claimedByUserId)}{$giftSubscription->claimedByUser->username}{else}-{/if}</td>
                    <td>{$giftSubscription->plan->name}</td>
                    <td>{$giftSubscription->billingCycleKey}</td>
                    <td>{$giftSubscription->duration}</td>
                    <td>{$compReasons[$giftSubscription->complimentaryReason]}</td>
                    <td>{$giftSubscription->createdTs|date_format:"%Y-%m-%d"}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>

        {$pagination->render()}
    </div>
</div>