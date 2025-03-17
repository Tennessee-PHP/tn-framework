{if empty($roles)}
    <small class="mb-3">This group currently does not contain any roles.</small>
{/if}
{foreach $roles as $child}
    {if is_array($child->children)}
        <div class="border p-2 my-4">
            <h3>{$child->readable}</h3>
            <div class="px-2">
                {include file="./RolesChildren.tpl" roles=$child->getChildren(false)}
            </div>
        </div>
    {/if}
{/foreach}
<div class="row">
    {foreach $roles as $child}
        {if !is_array($child->children)}
            <div class="col-4">
                {include file="./RolesRole.tpl" role=$child}
            </div>
        {/if}
    {/foreach}
</div>
