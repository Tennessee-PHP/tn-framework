<div class="{$classAttribute}" id="{$idAttribute}">
    <form id="edit_vouchercode">

        {if !$isPhantom}<input type="hidden" name="id" value="{$voucher->id}"/>{/if}
        {if $deactivate}<input type="hidden" name="id" value="{$deactivate}"/>{/if}

        <div class="row pb-4">
            <h2>Promo Code Details</h2>
            <div class="col-3 d-flex flex-column">
                <label for="editvoucher_name" class="form-label">Name</label>
                <input type="text" id="editvoucher_name" name="name" class="form-control"
                       value="{if !$isPhantom}{$voucher->name}{/if}">
            </div>
            <div class="col-3 d-flex flex-column">
                <label for="editvoucher_code" class="form-label">Code</label>
                <input type="text" class="text-uppercase form-control" id="editvoucher_code" name="code"
                       value="{if !$isPhantom}{$voucher->code}{/if}">
            </div>
            <div class="col-3 d-flex flex-column">
                <label for="editvoucher_discount" class="form-label">Discount %</label>
                <input type="number" id="editvoucher_discount" name="discount" class="form-control"
                       value="{if !$isPhantom}{$voucher->discountPercentage}{/if}">
            </div>
            <div class="col-3 d-flex flex-column">
                <label for="editvoucher_numtransactions" class="form-label">Apply</label>
                <select id="editvoucher_numtransactions" name="numTransactions" class="form-control">
                    <option value="0" {if $voucher->numTransactions === 0}selected{/if}>Forever</option>
                    <option value="1" {if $voucher->numTransactions === 1}selected{/if}>First Payment Only
                    </option>
                </select>
            </div>
        </div>

        <hr>

        <div class="row pb-3">
            <div class="col-4 d-flex flex-column">
                <label for="editvoucher_start" class="form-label">Start</label>
                <input type="date" id="editvoucher_start" name="start" class="form-control"
                       value="{if !$isPhantom && $voucher->startTs > 0}{$voucher->startTs|date_format:"%Y-%m-%d"}{/if}">
            </div>
            <div class="col-4 d-flex flex-column">
                <label for="editvoucher_end" class="form-label">End</label>
                <input type="date" id="editvoucher_end" name="end" class="form-control"
                       value="{if !$isPhantom && $voucher->endTs > 0}{$voucher->endTs|date_format:"%Y-%m-%d"}{/if}">
            </div>
            <div class="col-4 d-flex flex-column">
                <p>Promo Codes with start/end times that do not encapsulate the current date are
                    considered
                    deactivated. These codes will automatically take effect once the start date is reached
                    or the
                    end date is modified.</p>
            </div>
        </div>

        <div class="row pb-4">
            <h3 class="pb-2">Select ws</h3>
            {foreach $plans as $plan}
                {if $plan->paid}
                    <div class="col-4">
                        <input type="checkbox" name="{$plan->key}" id="editvoucher_planKey_{$plan->key}" class="form-check d-inline-block mb-0"
                               value="{$plan->key}"
                                {foreach $activePlans as $planKey}
                                    {if $planKey === $plan->key}checked{/if}
                                {/foreach}
                        >
                        <label for="editvoucher_planKey_{$plan->key}" class="form-label d-inline-block mt-0">{$plan->name}</label>
                    </div>
                {/if}
            {/foreach}
        </div>

        <div class="text-center">
            <input type="submit" class="btn btn-primary" value="Save Promo Code">
            <div id="edit-loading" style="text-align:center; display:none;">
                <div class="spinner-border" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>
        </div>
    </form>

    <h2 class="mt-5">Usage Stats</h2>
    <p class="alert alert-warning">Usage stats for voucher codes are currently disabled pending a new counting system.</p>
    {*
    {if !$isPhantom}
        <table class="table">
            <thead>
            <tr>
                <th>Total Uses</th>
                <th>Total Discount</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>{$totalUses}</th>
                <th>${$totalDiscount}</th>
            </tr>
            </tbody>
        </table>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Day</th>
                <th>Number of Uses</th>
                <th>Discounted Total $</th>
            </tr>
            </thead>
            <tbody>
            {if $totalUses !== 0}
                {foreach $dataSet as $data}
                    <tr>
                    {if key_exists("usesByCode:{$voucher->code}", $data->data["usesByCode:"]->children)}
                        {$data->data["usesByCode:"]->children["usesByCode:{$voucher->code}"]->value}
                        <th>{$data->label}</th>
                        <th>
                            {if $data->data["usesByCode:"]->isParent}
                                {if key_exists("usesByCode:{$voucher->code}", $data->data["usesByCode:"]->children)}
                                    {$data->data["usesByCode:"]->children["usesByCode:{$voucher->code}"]->value}
                                {else}
                                    0
                                {/if}
                            {else}
                                0
                            {/if}
                        </th>
                        <th>
                            {if $data->data["discount\$ByCode:"]->isParent}
                                {if key_exists("discount\$ByCode:{$voucher->code}", $data->data["discount\$ByCode:"]->children)}
                                    ${$data->data["discount\$ByCode:"]->children["discount\$ByCode:{$voucher->code}"]->value}
                                {else}
                                    0
                                {/if}
                            {else}
                                0
                            {/if}
                        </th>
                        </tr>
                    {/if}
                {/foreach}
            {/if}
            </tbody>
        </table>
    {/if}*}
</div>