<div class="{$classAttribute}" id="{$idAttribute}">
    <p>
        <a href="{$BASE_URL}staff/sales/voucher-codes/new" class="btn btn-primary">Add New Promo Code</a>
    </p>
    <div class="table-reponsive" id="voucher_codes_table_wrapper">
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Discount</th>
                <th>Start</th>
                <th>End</th>
                <th>Apply</th>
                <th>Plans</th>
                <th>Edit</th>
                <th>Deactivate</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="10" class="bg-success text-white">
                    <b>Active Vouchers - between start/end dates</b>
                </td>
            </tr>
            {foreach $codes as $code}
                {if $code->endTs === 0 || $code->startTs !== $code->endTs && $code->startTs <= $time && $code->endTs >= $time}
                    {assign var='expired' value=false}
                    {include file="./ListVoucherCodesTrs.tpl" code=$code}
                {/if}
            {/foreach}
            <tr>
                <td colspan="10" class="bg-danger text-white">
                    <b>Inactive Vouchers - not between start/end dates</b>
                </td>
            </tr>
            {foreach $codes as $code}
                {if $code->startTs === $code->endTs && $code->endTs !== 0 || $code->startTs > $time && $code->endTs !== 0 || $code->endTs < $time && $code->endTs !== 0}
                    {assign var='expired' value=true}
                    {include file="./ListVoucherCodesTrs.tpl" code=$code}
                {/if}
            {/foreach}
            </tbody>
        </table>
    </div>
</div>