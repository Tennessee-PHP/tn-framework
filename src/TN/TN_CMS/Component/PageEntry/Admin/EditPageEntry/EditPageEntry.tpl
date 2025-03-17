<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}" {if $pageEntry}data-pageEntryId="{$pageEntry->id}"{/if}>
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Page Entry" 
        message="Loading Page Entry..."
    }

    <div class="modal-header">
        <span class="modal-title">{if $pageEntry}{$BASE_URL}<b>{$pageEntry->path}</b>{/if}</span>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>


    <div class="modal-body">
        <div class="component-loading d-none d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only"></span>
            </div>
        </div>


        <div class="component-body">

            <form action="" method="POST">

                <div class="row mb-3">
                    <label for="edit_page_entry_title" class="col-sm-3 col-form-label">Title</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="edit_page_entry_title" value="{$pageEntry->title}"
                               placeholder="{$pageEntry->originalTitle}">
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="edit_page_entry_subtitle" class="col-sm-3 col-form-label">Sub Title</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="edit_page_entry_subtitle"
                               value="{$pageEntry->subtitle}" placeholder="{$pageEntry->originalSubtitle}">
                    </div>

                </div>

                <div class="mb-3">
                    <label for="edit_page_entry_description" class="col-form-label">Description</label>
                    <textarea class="form-control" id="edit_page_entry_description">{$pageEntry->description}</textarea>

                </div>

                <div class="row mb-3">
                    <label for="edit_page_entry_weight" class="col-sm-3 col-form-label">Weight</label>
                    <div class="col-sm-3">
                        <select class="form-control" id="edit_page_entry_weight">
                            {for $i = 10 to 0 step -1}
                                <option value="{$i}" {if $pageEntry->weight == $i}selected{/if}>{$i}</option>
                            {/for}
                        </select>
                    </div>

                    <div class="col-sm-6">
                        <small class="form-text text-muted">Higher weights mean more prominent placements on content
                            listing pages.</small>
                    </div>
                </div>

            </form>

            <form id="page_entry_thumbnail_form" method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <label for="page_entry_thumbnail" class="col-sm-3 col-form-label">Thumbnail (Horizontal)</label>
                    <div class="col-sm-9">
                        <input class="form-control" accept="image/png,image/jpeg,image/img" type="file"
                               id="page_entry_thumbnail" name="image">

                        <input type="hidden" id="page_entry_thumbnail_src" name="thumbnailSrc"
                               value="{$pageEntry->thumbnailSrc}"/>

                        <img src="{$pageEntry->thumbnailSrc}" id="page_entry_thumbnail_src_img"
                             class="w-50 {if empty($pageEntry->thumbnailSrc)}d-none{/if}"/>
                    </div>
                </div>
            </form>

            <form id="page_entry_vthumbnail_form" method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <label for="page_entry_vthumbnail" class="col-sm-3 col-form-label">Thumbnail (Vertical)</label>
                    <div class="col-sm-9">
                        <input class="form-control" accept="image/png,image/jpeg,image/img" type="file"
                               id="page_entry_vthumbnail" name="image">

                        <input type="hidden" id="page_entry_vthumbnail_src" name="vThumbnailSrc"
                               value="{$pageEntry->vThumbnailSrc}"/>

                        <img src="{$pageEntry->vThumbnailSrc}" id="page_entry_vthumbnail_src_img"
                             class="w-50 {if empty($pageEntry->vThumbnailSrc)}d-none{/if}"/>
                    </div>
                </div>
            </form>

            {if $tagEditor}{$tagEditor->render()}{/if}

        </div>
    </div>


    <div class="modal-footer">
        {if $editContentUrl}
            <a href="{$editContentUrl}" class="btn btn-outline-primary me-auto">{$editContentText}</a>
        {/if}
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="save_edit_page_entry_button">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            <span class="label">Save</span>
        </button>
    </div>
</div>