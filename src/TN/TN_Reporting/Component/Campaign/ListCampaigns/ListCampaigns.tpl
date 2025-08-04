<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    <div class="d-flex justify-content-right my-3">
        <a href="{$BASE_URL}staff/campaigns/new" class="btn btn-primary">Add New Campaign</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>Campaign Key</th>
                <th>Funnel Name</th>
                <th>Promo Code</th>
                <th>Use Base URL</th>
                <th>URL</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            {foreach $campaigns as $campaign}
                <tr class="campaign-table-row{if $campaign->archived} table-secondary{/if}" data-toggle="tooltip" data-placement="bottom"
                    title="{$campaign->notes|strip_tags:true}">
                    <td class="campaign-link"><a href="{$BASE_URL}staff/campaigns/edit/{$campaign->id}">{$campaign->key}</a></td>
                    <td>{$campaign->funnel->name}</td>
                    <td>{if $campaign->voucherCodeId !== 0}<a href="{$BASE_URL}staff/sales/voucher-codes/edit/{$campaign->voucherCodeId}">{$campaign->voucherCode->code}</a>{else}-{/if}</td>
                    <td>{if $campaign->useBaseUrl}Yes{else}No{/if}</td>
                    <td><a href="{$campaign->getUrl()}">{$campaign->getUrl()}<a></td>
                    <td>
                        {if $campaign->archived}
                            <span class="badge bg-secondary">Archived</span>
                        {else}
                            <span class="badge bg-success">Active</span>
                        {/if}
                    </td>
                    <td>
                        <a href="{$BASE_URL}staff/campaigns/edit/{$campaign->id}" class="btn btn-sm btn-primary text-nowrap"><i
                                    class="bi bi-pencil-fill"></i> Edit</a>
                        <br>
                        <button type="button" 
                                class="btn btn-sm {if $campaign->archived}btn-outline-success{else}btn-outline-secondary{/if} toggle-archive-btn text-nowrap" 
                                data-campaign-id="{$campaign->id}"
                                data-archived="{if $campaign->archived}1{else}0{/if}">
                            <i class="bi {if $campaign->archived}bi-arrow-repeat{else}bi-archive{/if}"></i>
                            {if $campaign->archived}Unarchive{else}Archive{/if}
                        </button>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>

    {$pagination->render()}
</div>