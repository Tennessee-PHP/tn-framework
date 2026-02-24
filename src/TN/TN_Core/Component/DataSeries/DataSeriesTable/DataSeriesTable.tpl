<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            {foreach $dataSeries->columns as $column}
                <th>{$column->label}</th>
            {/foreach}
        </tr>
        </thead>
        <tbody>
        {foreach $dataSeries->entries as $entry}
            {assign var=isAllCampaignsRow value=false}
            {foreach $dataSeries->columns as $column}
                {if $column->key === 'campaign' && $entry->getValue($column) === 'All Campaigns'}
                    {assign var=isAllCampaignsRow value=true}
                {/if}
            {/foreach}
            <tr>
                {foreach $dataSeries->columns as $column}
                    <td>{if $column->emphasize || $isAllCampaignsRow}<b>{/if}{$entry->getDisplayValue($column)}{if $column->emphasize || $isAllCampaignsRow}</b>{/if}</td>
                {/foreach}
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>