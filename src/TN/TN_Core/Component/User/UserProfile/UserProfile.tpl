<div  class="{$classAttribute}" id="{$idAttribute}">
    <ul class="nav nav-pills mb-3">
        {foreach $tabs as $tab}
        <li class="nav-item">
            <a class="nav-link{if $tab.selected} active{/if}" aria-current="page" href="{path route="TN_Core:User:userProfile" username=$username|urlencodeperiods}/{$tab.key}">{$tab.readable}</a>
        </li>
        {/foreach}
        {if $canLoginAsUser}
            <li class="nav-item">
                <a class="btn btn-primary" href="{path route="TN_Core:User:loginAsUser" userId=$user->id}"><i class="bi bi-key-fill"></i>Login As This User</a>
            </li>
        {/if}
    </ul>

    {$tabComponent->render()}

</div>