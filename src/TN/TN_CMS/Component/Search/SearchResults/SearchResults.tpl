<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}" data-search="{$search}" data-result-selected-url="{path route='TN_CMS:Search:searchResultSelected'}">
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Search Results" 
        message="Loading Search Results..."
    }

    <div class="search-results">
        {foreach $pageEntries as $pageEntry}
            {if $pageEntry@first}<div class="list-group list-group-flush">{/if}
               {if $pageEntry->readableContentType === 'Article'}
                   <a data-page-entry-id="{$pageEntry->id}"  href="{$BASE_URL}{$pageEntry->path}"class="list-group-item list-group-item-action">
                       <div class="d-flex align-items-center">
                           <span class="flex-fill">{$pageEntry->title}</span>
                           <span class="badge bg-primary ms-auto">Article</span>
                       </div>
                           <small class="text-secondary">{$pageEntry->ts|date_format:"%B %e, %Y"}</small>
                   </a>
                   {else}
                   <a data-page-entry-id="{$pageEntry->id}"  href="{$BASE_URL}{$pageEntry->path}"class="list-group-item list-group-item-action">
                       {$pageEntry->title}
                   </a>
               {/if}

            {if $pageEntry@last}</div>{/if}
            {foreachelse}
        {/foreach}
    </div>
</div>