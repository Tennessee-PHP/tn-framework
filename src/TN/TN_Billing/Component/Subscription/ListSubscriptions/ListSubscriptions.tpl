<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    <form class="filter-form mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="plan-select" class="form-label">Plan Level</label>
                {$planSelect->render()}
            </div>
            <div class="col-md-3">
                <label for="billing-cycle-select" class="form-label">Billing Cycle</label>
                {$billingCycleSelect->render()}
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">User ID</th>
                    <th scope="col">Username</th>
                    <th scope="col">Email</th>
                    <th scope="col">Subscription Level</th>
                    <th scope="col">Billing Cycle</th>
                    <th scope="col">Subscription Start</th>
                    <th scope="col">End Date</th>
                    <th scope="col">Next Payment</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                {foreach $subscriptions as $subscription}
                    <tr>
                        <td>{$subscription->userId|escape}</td>
                        <td>
                            {if $subscription->user}
                                <a href="{path route='TN_Core:User:userProfile' username=$subscription->user->username}" class="text-decoration-none">
                                    {$subscription->user->username|escape}
                                </a>
                            {else}
                                -
                            {/if}
                        </td>
                        <td>
                            {if $subscription->user}
                                {$subscription->user->email|escape}
                            {else}
                                -
                            {/if}
                        </td>
                        <td>{$subscription->getPlan()->name|escape}</td>
                        <td>{$subscription->getBillingCycle()->name|escape}</td>
                        <td>{$subscription->startTs|date_format:"%Y-%m-%d"}</td>
                        <td>
                            {if $subscription->endTs > 0}
                                {$subscription->endTs|date_format:"%Y-%m-%d"}
                            {else}
                                -
                            {/if}
                        </td>
                        <td>
                            {if $subscription->nextTransactionTs > 0}
                                {$subscription->nextTransactionTs|date_format:"%Y-%m-%d"}
                            {else}
                                -
                            {/if}
                        </td>
                        <td>
                            {if $subscription->active && $subscription->nextTransactionTs > 0}
                                <span class="badge bg-success">Active</span>
                            {elseif $subscription->active && $subscription->nextTransactionTs == 0}
                                <span class="badge bg-secondary">Cancelled</span>
                            {elseif $subscription->lastTransactionFailure > 0}
                                <span class="badge bg-warning">Grace Period</span>
                            {else}
                                <span class="badge bg-danger">Inactive</span>
                            {/if}
                        </td>
                    </tr>
                {foreachelse}
                    <tr>
                        <td colspan="9" class="text-center text-muted">No subscriptions found</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        {$pagination->render()}
    </div>
</div> 