<select class="{$classAttribute} form-select" id="{$idAttribute}" data-request-key="{$requestKey}" data-value="{if $selected}$selected->id{/if}">
<option value="">{$allLabel}</option>
    {foreach $options as $option}
        <option value="{$option->id}"{if $option->id === $selected->id} selected{/if}>{$option->$displayProperty}</option>
    {/foreach}
</select>