<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    <form class="filter-form mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="cancellation-reason-filter" class="form-label">Reason</label>
                <select class="form-select" name="reasonCode" id="cancellation-reason-filter">
                    <option value="">All reasons</option>
                    {foreach $reasonOptions as $code => $label}
                        <option value="{$code|escape}"{if $reasonCode === $code} selected{/if}>{$label|escape}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-md-3">
                <label for="cancellation-outcome-filter" class="form-label">Outcome</label>
                <select class="form-select" name="outcome" id="cancellation-outcome-filter">
                    <option value="">All outcomes</option>
                    {foreach $outcomeOptions as $code => $label}
                        <option value="{$code|escape}"{if $outcome === $code} selected{/if}>{$label|escape}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-md-2">
                <label for="cancellation-date-from" class="form-label">From</label>
                <input type="date" class="form-control" name="dateFrom" id="cancellation-date-from"
                       value="{if $dateFrom}{$dateFrom|escape}{/if}">
            </div>
            <div class="col-md-2">
                <label for="cancellation-date-to" class="form-label">To</label>
                <input type="date" class="form-control" name="dateTo" id="cancellation-date-to"
                       value="{if $dateTo}{$dateTo|escape}{/if}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="cancellation-attempts-list">
        {foreach $rows as $row}
            {assign var="attempt" value=$row.attempt}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="fw-semibold">
                                {if $row.user}
                                    <a href="{path route='TN_Core:User:userProfile' username=$row.user->username}" class="text-decoration-none">
                                        {$row.user->username|escape}
                                    </a>
                                {else}
                                    <span class="text-muted">Unknown user</span>
                                {/if}
                            </div>
                            <div class="text-muted small">
                                {$attempt->createdTs|date_format:"%B %e, %Y %H:%M"}
                            </div>
                        </div>
                        <div>
                            {if $row.outcomeLabel}
                                <span class="badge bg-secondary">{$row.outcomeLabel|escape}</span>
                            {else}
                                <span class="badge bg-light text-muted border">In progress</span>
                            {/if}
                        </div>
                    </div>

                    <dl class="row mb-0 small">
                        <dt class="col-sm-3 col-md-2 text-muted">Plan</dt>
                        <dd class="col-sm-9 col-md-4 mb-2">{$row.planName|escape}</dd>

                        <dt class="col-sm-3 col-md-2 text-muted">Billing</dt>
                        <dd class="col-sm-9 col-md-4 mb-2">{$attempt->billingCycleKeyAtAttempt|escape}</dd>

                        <dt class="col-sm-3 col-md-2 text-muted">Reason</dt>
                        <dd class="col-sm-9 col-md-4 mb-2">{$row.reasonLabel|escape}</dd>

                        <dt class="col-sm-3 col-md-2 text-muted">Offer</dt>
                        <dd class="col-sm-9 col-md-4 mb-2">{$row.offerLabel|escape}</dd>
                    </dl>

                    {if $attempt->comment}
                        <div class="mt-3 pt-3 border-top">
                            <div class="text-muted small mb-1">Comment</div>
                            <div class="cancellation-attempt-comment text-break" style="white-space: pre-wrap;">{$attempt->comment|escape}</div>
                        </div>
                    {/if}
                </div>
            </div>
        {foreachelse}
            <p class="text-muted mb-0">No cancellation attempts found.</p>
        {/foreach}
    </div>

    {$pagination->render()}
</div>
