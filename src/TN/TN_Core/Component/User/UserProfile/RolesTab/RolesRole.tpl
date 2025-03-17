<div class="col mb-2">
    <input type="checkbox" name="{$role->key}" id="checkbox_{$role->key}"
           {if in_array($role->key, $userRoleKeys)}checked{/if}>
    <label for="checkbox_{$role->key}"><b>{$role->readable}</b></label>
    <small class="d-block">{$role->description}</small>
</div>