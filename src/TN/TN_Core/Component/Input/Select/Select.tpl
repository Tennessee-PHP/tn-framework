<select  id="{$idAttribute}" class="{$classAttribute} form-select" data-request-key="{$requestKey}" name="{$requestKey}"{if $multiple} multiple="multiple"  style="display:none;"{/if}>
    {foreach $options as $option}
        <option value="{$option->key}" {if $selected && $option->key == $selected->key}selected{/if}>{$option->label}</option>
    {/foreach}
</select>