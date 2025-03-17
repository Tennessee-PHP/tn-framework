<div class="modal fade {$classAttribute}" id="search_modal" data-bs-keyboard="true"
     tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="input-group">
                    <span class="input-group-text" id="search_icon"><i class="bi bi-search"></i></span>
                    <input type="search" placeholder="Search website..." class="form-control" id="search_input">
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {$searchResults->render()}
            {if $canEditResults}
            <div class="modal-footer d-none">
                <a data-base-url="{path route='TN_CMS:PageEntry:adminListPageEntries'}?filter_search="
                   class="btn btn-primary disabled"><i class="bi bi-gear-wide-connected"></i> Edit Search Results</a>
            </div>
            {/if}
        </div>
    </div>
</div>
