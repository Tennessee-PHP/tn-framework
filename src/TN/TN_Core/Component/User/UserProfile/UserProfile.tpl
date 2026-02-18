<div  class="{$classAttribute}" id="{$idAttribute}">
    <ul class="nav nav-pills mb-3">
        {foreach $tabs as $tab}
        <li class="nav-item">
            <a class="nav-link{if $tab.selected} active{/if}" aria-current="page" href="{path route="TN_Core:User:userProfile" username=$user->id}/{$tab.key}">{$tab.readable}</a>
        </li>
        {/foreach}
    </ul>

    {if $isOwnProfile || $canLoginAsUser}
    <div class="mb-4">
        {if $isOwnProfile}
            <div class="me-2 d-inline-block">
                <p class="small text-muted mb-1">End all your sessions on other devices and browsers. You’ll need to sign in to {$SITE_NAME} again everywhere.</p>
                <form method="POST" action="{$BASE_URL}user/revoke-all-tokens" class="d-inline">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-right"></i> Log Out Everywhere</button>
                </form>
            </div>
        {/if}
        {if $canLoginAsUser && !$isOwnProfile}
            <a class="btn btn-primary me-2" href="{path route="TN_Core:User:loginAsUser" userId=$user->id}"><i class="bi bi-key-fill"></i> Login As This User</a>
        {/if}
        {if $canLoginAsUser && !$isOwnProfile}
            <form method="POST" action="{$BASE_URL}staff/users/user/{$user->id}/revoke-all-tokens" class="d-inline" data-revoke-sessions-ajax>
                <button type="submit" class="btn btn-danger"><i class="bi bi-shield-x"></i> Revoke All Sessions</button>
            </form>
        {/if}
    </div>
    {/if}

    {$tabComponent->render()}

</div>