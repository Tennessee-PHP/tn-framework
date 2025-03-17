<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}" data-sortby="{$sortBy}" data-sortorder="{$sortOrder}">
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Search Queries" 
        message="Loading Search Queries..."
    }

    <div class="card">
        <div class="card-body">
            <p>A table of the searches performed on the website, with options to list by frequency and rate at which users
                select one of the search results.</p>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th><a href="" class="sort-by" data-sortby="query">Search Query{if $sortBy eq 'query'} <i
                                    class="bi bi-arrow-{if $sortOrder eq 'DESC'}down{else}up{/if}"></i>{/if}</a></th>
                        <th><a href="" class="sort-by" data-sortby="totalCount">Total Count{if $sortBy eq 'totalCount'} <i
                                    class="bi bi-arrow-{if $sortOrder eq 'DESC'}down{else}up{/if}"></i>{/if}</a>
                            <div class="d-flex">
                                <label for="minTotalCount" class="mt-2 me-2">Minimum: </label>
                                <select id="minTotalCount" name="mintotalcount" class="form-select form-select-sm" style="width:100px !important;">
                                    {foreach $minCountOptions as $minCountOption}
                                        <option value="{$minCountOption}"{if $minCount eq $minCountOption} selected{/if}>{$minCountOption}</option>
                                    {/foreach}
                                </select>
                            </div></th>
                        <th><a href="" class="sort-by" data-sortby="totalSelectedResults">Selected
                                Results{if $sortBy eq 'totalSelectedResults'} <i
                                        class="bi bi-arrow-{if $sortOrder eq 'DESC'}down{else}up{/if}"></i>{/if}</a></th>
                        <th><a href="" class="sort-by" data-sortby="selectedRate">Result Selection
                                Rate{if $sortBy eq 'selectedRate'} <i
                                        class="bi bi-arrow-{if $sortOrder eq 'DESC'}down{else}up{/if}"></i>{/if}</a>
                        </th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $searchQueries as $searchQuery}
                        <tr>
                            <td>{$searchQuery->query}</td>
                            <td>{$searchQuery->totalCount}</td>
                            <td>{$searchQuery->totalSelectedResults}</td>
                            <td>{($searchQuery->selectedRate*100)|round}%</td>
                            <td><a class="btn btn-outline-primary btn-sm"
                                   href="{$BASE_URL}staff/page-entries/edit?filter_search={$searchQuery->query|escape:'url'}">EDIT
                                    RESULTS</a></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>

            {$pagination->render()}

            <div class="alert alert-danger">
                <a class="btn btn-danger float-end ms-2" href="#clearresults" data-bs-toggle="modal" data-bs-target="#confirm_clear_queries_modal"><i class="bi bi-folder-x"></i> Delete Search Query Data</a>
                <p class="flex-fill mb-0">After a comprehensive view of search results, you may wish to clear all saved data so the changes made can start recording new data afresh. <br /><b>This cannot be undone!</b></p>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="confirm_clear_queries_modal" tabindex="-1" aria-labelledby="confirm_clear_queries_modal_label" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirm_clear_queries_modal_label">Are you sure?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="alert alert-warning">Are you sure you want to delete all search query data? <b>This cannot be undone!</b></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" id="confirm_clear_queries_btn"><i class="bi bi-folder-x"></i> Delete Search Query Data</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>