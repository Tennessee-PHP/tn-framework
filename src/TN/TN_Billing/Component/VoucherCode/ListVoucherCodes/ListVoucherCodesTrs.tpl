<tr>
    <td><a href="{$BASE_URL}staff/sales/voucher-codes/edit/{$code->id}">{$code->name}</a></td>
    <td>{$code->code}</td>
    <td>{$code->discountPercentage}%</td>
    <td>{if $code->startTs === 0}-{else}{$code->startTs|date_format:"%Y-%m-%d"}{/if}</td>
    <td>{if $code->endTs === 0}-{else}{$code->endTs|date_format:"%Y-%m-%d"}{/if}</td>
    <td>{if $code->numTransactions === 0}Forever{else}First Only{/if}</td>
    <td>{foreach $code->getPlans() as $plan}
            {$plan->name}{if !$plan@last}, {/if}
        {/foreach}</td>
    <td {if $expired} colspan="2" {/if}>
        <a href="{$BASE_URL}staff/sales/voucher-codes/edit/{$code->id}" class="btn btn-sm btn-secondary"><i
                    class="bi bi-pencil-fill"></i> Edit</a>
    </td>
    {if !$expired}
        <td>
            <a href="{$BASE_URL}staff/sales/voucher-codes/edit/{$code->id}/deactivate" class="btn btn-sm btn-danger"><i
                        class="bi bi-trash-fill"></i> Deactivate</a>
        </td>
    {/if}
</tr>