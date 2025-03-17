<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Page Entries" 
        message="Loading Page Entries..."
    }

    <div class="card">
        <form action="" method="GET" class="filter-form mb-4">
            <h3>Find A Specific Page</h3>
            <div class="row mb-3">
                <label for="path_filter" class="col-sm-2 col-form-label">URL</label>
                <div class="col-sm-10 col-md-6">
                    <small>{$BASE_URL}</small><input type="text" placeholder="filter by URL..." name="path"
                                                     id="path_filter" value="{$pathFilter}" class="w-100 form-control"/>
                </div>
                <div class="col-sm-12 col-md-4 form-text">Searches for pages by their URL on the website. Use this to find
                    specific pages you want to edit.
                </div>
            </div>

            <div class="row mb-3">
                <label for="title_filter" class="col-sm-2 col-form-label">Title</label>
                <div class="col-sm-10 col-md-6">
                    <input type="text" placeholder="filter by title..." name="title"
                           id="title_filter" value="{$titleFilter}" class="w-100 form-control"/>
                </div>
                <div class="col-sm-12 col-md-4 form-text">Searches for pages by their title on the website. Use this to find
                    specific pages you want to edit.
                </div>
            </div>

            <hr/>

            <h3>Edit Content Landing Pages or Search Results</h3>

            <div class="row mb-3">
                <label for="tag_filter" class="col-sm-2 col-form-label">Tag</label>
                <div class="col-sm-10 col-md-6">
                    <input type="text" placeholder="filter by tag..." name="tag"
                           id="tag_filter" value="{$tagFilter}" class="w-100 form-control"/>
                </div>
                <div class="col-sm-12 col-md-4 form-text">For <b>Content Landing Pages</b>, each page is associated with a
                    tag. Type that tag into this box to find and re-organise all its content.
                </div>
            </div>

            or

            <div class="row mb-3">
                <label for="search_filter" class="col-sm-2 col-form-label">Site Search</label>
                <div class="col-sm-10 col-md-6">
                    <input type="text" placeholder="mirror site search results..." name="search"
                           id="search_filter" value="{$searchFilter}" class="w-100 form-control"/>
                </div>
                <div class="col-sm-12 col-md-4 form-text">Replicate a <b>Site Search</b>, re-organise or amend its results,
                    and see why each item is being returned.
                </div>
            </div>

            <h3>More Filters</h3>

            <div class="row mb-3 mx-2">
                <div class="form-check col-sm-12 col-md-3">
                    <input class="form-check-input" type="checkbox" value="1" name="notags" id="notags_filter"
                           {if $onlyNoTags}checked{/if}>
                    <label class="form-check-label" for="notags_filter">
                        <i class="bi bi-bookmark-x-fill text-danger"></i> Only content with no tags
                    </label>
                </div>

                <div class="form-check col-sm-12 col-md-9">
                    <input class="form-check-input" type="checkbox" value="1" name="nothumbnail" id="nothumbnail_filter"
                           {if $onlyNoThumbnail}checked{/if}>
                    <label class="form-check-label" for="nothumbnail_filter">
                        <i class="bi bi-image-fill text-danger"></i> Only content with no thumbnail
                    </label>
                </div>
            </div>

            <div class="row mb-3 mx-2">
                {foreach $contentTypeLabels as $contentType}
                    <div class="form-check col-sm-12 col-md-3">
                        <input class="form-check-input content-filter" type="checkbox" value="1"
                               name="{$contentType|replace:' ':'_'}"
                               id="{$contentType|replace:' ':'_'}_filter" {if in_array($contentType, $contentTypeFilters)} checked{/if}>
                        <label class="form-check-label" for="{$contentType|replace:' ':'_'}_filter">
                            Show {$contentType}s
                        </label>
                    </div>
                {/foreach}
            </div>


            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        {if !empty($searchFilter)}
            <h2>Search Statistics for the search <span class="bg-info">"{$searchFilter}"</span></h2>
            {if !$searchQuery}
                <p>This search term has never been used.</p>
            {else}
                <ul>
                    <li><b>{$searchQuery->totalCount}</b> total searches</li>
                    <li><b>{$searchQuery->totalSelectedResults}</b> total selected results</li>
                    <li><b>{$searchQuery->selectedRate*100|round}%</b> - the rate at which users select one of the results
                    </li>
                </ul>
            {/if}
        {/if}


        <h2>
            {if !empty($searchFilter)}Content returned by searching <span class="bg-warning">"{$searchFilter}"</span>
            {elseif !empty($tagFilter)}Content tagged with "{$tagFilter}"
            {else}All Content{/if}
            {if !empty($pathFilter) || !empty($titleFilter)}
                filtered by
                {if !empty($pathFilter)}
                    URL matching "{$pathFilter}" {if !empty($titleFilter)}and{/if}
                {/if}
                {if !empty($titleFilter)}
                    title matching "{$titleFilter}"
                {/if}
            {/if}
        </h2>
        <p></p>

        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th colspan="5" class="border-0" style="text-align:center !important;">This Page</th>
                    {if !empty($searchFilter)}
                        <th colspan="1" class="border-0"  style="text-align:center !important;"></th>
                    {/if}
                    <th colspan="{if !empty($searchFilter)}5{else}3{/if}" class="border-0 table-warning"  style="text-align:center !important;">Sort Factor Breakdown</th>
                    {if !empty($searchFilter)}
                        <th colspan="2" class="border-0 table-info" style="text-align:center !important;">Search Selection</th>
                    {/if}
                    <th class="border-0"></th>
                </tr>
                <tr>
                    <th class="rounded-0">Page URL</th>
                    <th>Title</th>
                    <th>Content Type</th>
                    <th>Thumbnail</th>
                    <th># of Tags</th>
                    {if !empty($searchFilter)}
                        <th>Matched By:</th>
                    {/if}
                    <th class="table-warning">Date</th>
                    <th class="table-warning">Weight</th>
                    {if !empty($searchFilter)}
                        <th class="table-warning">Title Match</th>
                        <th class="table-warning">Tags Match</th>
                    {/if}
                    <th class="table-warning">Score</th>
                    {if !empty($searchFilter)}
                        <th class="table-info">Count</th>
                        <th class="table-info">Rate</th>
                    {/if}
                    <th class="rounded-0"></th>
                </tr>
                </thead>
                <tbody>
                {assign var=lastPrimary value=false}
                {foreach $pageEntries as $pageEntry}
                    {if $pageEntry->primary neq $lastPrimary}
                        <tr class="table-dark">
                            <td colspan="{if !empty($searchFilter)}14{else}9{/if}"><b>
                                    {if $pageEntry->primary}
                                        <i class="bi bi-bookmark-plus-fill"></i>
                                        Primary Content (always listed first for this tag){else}
                                        <i class="bi bi-bookmark-plus"></i>
                                        Non-Primary Content{/if}
                                </b></td>
                        </tr>
                        {assign var=lastPrimary value=$pageEntry->primary}
                    {/if}
                    <tr data-pageEntryId="{$pageEntry->id}" id="pageentry_{$pageEntry->id}"
                        {if $pageEntry->primary}class="table-primary"{/if}>
                        <td><a target="_blank"
                               href="{$BASE_URL}{$pageEntry->path}"><small>{$BASE_URL}</small><br/>{$pageEntry->path}</a>
                        </td>
                        <td class="pageentry-title">{$pageEntry->title}</td>
                        <td>{$pageEntry->readableContentType}</td>
                        <td>{if !empty($pageEntry->thumbnailSrc)}<img src="{$pageEntry->thumbnailSrc}"
                                                                      alt="{$pageEntry->title}"
                                                                      style="max-width:100px; max-height:50px;"/>{else}
                                <i class="bi bi-image-fill text-danger"></i>
                            {/if}</td>
                        <td>{$pageEntry->numTags}{if $pageEntry->numTags === 0}
                                <i class="bi bi-bookmark-x-fill text-danger"></i>
                            {/if}</td>
                        {if !empty($searchFilter)}
                            <td>{if !empty($pageEntry->matchedTag)}Tags: {foreach $pageEntry->matchedTags as $tag}{$tag}{if !$tag@last}, {/if}{/foreach}{else}Title{/if}</td>
                        {/if}
                        <td class="table-warning">{$pageEntry->timeFactor|round:2}
                            <br/><small>{if $pageEntry->alwaysCurrent}Always up-to-date{else}{$pageEntry->ts|date_format:"%Y-%m-%d %H:$i:%s"}{/if}</small>
                        </td>
                        <td class="table-warning">{$pageEntry->weight}</td>
                        {if !empty($searchFilter)}
                            <td class="table-warning">{$pageEntry->searchTitleFactor|round:2}</td>
                            <td class="table-warning">{$pageEntry->searchWordsFactor|round:2}</td>
                        {/if}
                        <td class="table-warning"><b>{$pageEntry->factor|round:2}</b></td>
                        {if !empty($searchFilter)}
                            <td class="table-info">{$pageEntry->searchSelectedCount}</td>
                            <td class="table-info">{($pageEntry->searchSelectedRate*100)|round}%</td>
                        {/if}
                        <td>
                            <a href="#" class="edit-page-entry btn btn-primary" data-bs-toggle="modal"
                               data-bs-target="#edit_page_entry_modal"><i class="bi bi-binoculars-fill"></i> Edit
                            </a>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {$pagination->render()}

        <div class="modal fade tn-modal" id="edit_page_entry_modal" data-bs-backdrop="static" tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    {$editPageEntry->render()}
                </div>
            </div>
        </div>
    </div>
</div>