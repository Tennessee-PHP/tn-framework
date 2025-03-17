<div class="flex-fill">

<div class="{if $pcDiff === 0}text-secondary{elseif $positive}text-success{else}text-danger{/if}">
    {if $pcDiff > 0}<i class="bi bi-arrow-up-circle-fill"></i>{/if}
    {if $pcDiff < 0}<i class="bi bi-arrow-down-circle-fill"></i>{/if}
    {if $pcDiff !== null}{$pcDiff|abs|number_format:2}%{else}-{/if} {$label}</div>
<div class="text-secondary">{$prefix}{$value|number_format:$decimals}{$suffix}</div>

</div>