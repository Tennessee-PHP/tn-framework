<div class="{$classAttribute}" id="{$idAttribute}">
    <form id="staff_roles_form" action="{path route="TN_Core:User:userProfileRolesTabSaveRoles" userId=$user->id}" method="post">
        <input type="hidden" id="editstafferroles_id_field" value="{$user->id}">

        <div id="staff_roles_wrapper">
            {foreach $roles as $role}
                <div class="border p-2 my-4">
                    {if is_array($role->children)}
                        <h3>{$role->readable}</h3>
                        {include file="./RolesChildren.tpl" roles=$role->getChildren(false)}
                    {else}
                        {include file="./RolesRole.tpl" role=$role}
                    {/if}
                </div>
            {/foreach}
        </div>

        <input type="hidden" value="{$user->id}" name="id" id="user_id">

        <div class="navbar d-flex align-items-center justify-content-center sticky-bottom">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            <input type="submit" class="submit-staffer-change btn btn-primary"
                   value="Save Staffer Roles">
        </div>
    </form>
</div>