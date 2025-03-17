<div  class="{$classAttribute}" id="{$idAttribute}">
    <ul class="nav nav-pills mb-3">
        {foreach $tabs as $tab}
        <li class="nav-item">
            <a class="nav-link{if $tab.selected} active{/if}" aria-current="page" href="{path route="TN_Core:User:userProfile" username=$username}/{$tab.key}">{$tab.readable}</a>
        </li>
        {/foreach}
    </ul>

    {$tabComponent->render()}

</div>