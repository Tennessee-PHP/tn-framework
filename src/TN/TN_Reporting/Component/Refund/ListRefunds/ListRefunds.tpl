<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    <div class="row">
        <div class="col col-md-6 mb-4">
            <label for="year_select" class="form-label">Year</label>
            <select class="form-control" id="year_select" data-request-key="year" data-value="{$year}">
                <option value="">All</option>
                {foreach $yearOptions as $yearOption}
                    <option value="{$yearOption}" {if $yearOption == $year}selected{/if}>{$yearOption}</option>
                {/foreach}
            </select>
        </div>
        <div class="col col-md-6">
            <label for="reason_select" class="form-label">Reason</label>
            <select class="form-control" id="reason_select" data-request-key="reason" data-value="{$reason}">
                <option value="">All</option>
                {foreach $reasonOptions as $reasonOption}
                    <option value="{$reasonOption@key}" {if $reasonOption@key == $reason}selected{/if}>{$reasonOption}</option>
                {/foreach}
            </select>
    </div>

    <div class="table-responsive">
        <table class="table mb-5">
            <thead>
            <tr>
                <th scope="col">Total Refunds</th>
                <th scope="col">Total Amount Refunded</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{$refundCount}</td>
                <td>${$refundTotal}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Username</th>
                <th scope="col">Email</th>
                <th scope="col">User ID</th>
                <th scope="col">Transaction ID</th>
                <th scope="col">Time Refunded</th>
                <th scope="col">Reason Refunded</th>
                <th scope="col">Comment</th>
                <th scope="col">Refunded Amount</th>
            </tr>
            </thead>
            <tbody>
            {foreach $refunds as $refund}
                <tr>
                    <td><a href="{$BASE_URL}{$refund->user->username|urlencodeperiods}/profile">{if $refund->user}{$refund->user->username}{else}-{/if}</a></td>
                    <td>{if $refund->user}{$refund->user->email}{else}-{/if}</td>
                    <td>
                        <a href="{$BASE_URL}{$refund->user->username|urlencodeperiods}/profile">{$refund->userId}</a>
                    </td>
                    <td>{$refund->transactionId}</td>
                    <td>{$refund->ts|date_format:"%Y-%m-%d"}</td>
                    <td>{$refund->reason}</td>
                    <td>{$refund->comment}</td>
                    <td>${$refund->amount}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>

    {$pagination->render()}
</div>