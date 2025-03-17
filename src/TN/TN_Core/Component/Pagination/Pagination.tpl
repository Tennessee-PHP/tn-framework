{if $pageOptions|@count >= 2}
    <nav aria-label="pagination" class="{$classAttribute}" data-request-key="{$requestKey}" data-value="{$page}" id="{$idAttribute}">
        <ul class="pagination justify-content-center">
            {foreach $pageOptions as $pageOption}
                <li class="page-item{if $pageOption.disabled} disabled{/if}{if $pageOption.active} active{/if}"
                    {if $pageOption.active}aria-current="page"{/if}>
                    <a class="page-link" href="#" data-page="{$pageOption.page}">{$pageOption.text}</a>
                </li>
            {/foreach}
        </ul>
    </nav>
{/if}
