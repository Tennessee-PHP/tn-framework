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
            <tr>
                {foreach $dataSeries->columns as $column}
                    <td>{if $column->emphasize}<b>{/if}{$entry->getDisplayValue($column)}{if $column->emphasize}</b>{/if}</td>
                {/foreach}
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>