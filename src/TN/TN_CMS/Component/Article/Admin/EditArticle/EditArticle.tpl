<div class="{$classAttribute}" id="{$idAttribute}" data-articleid="{$article->editId}" data-user-is-article-editor="{$userIsArticleEditor}">

    <div class="mt-5 pt-4">
        {$articleTitleEditor->render()}

        <h1 class="display-4">{$title}</h1>
        {if $subtitle}<p class="lead">{$subtitle}</p>{/if}


        <div class="modal fade tn-modal" id="edit_page_entry_modal" data-bs-backdrop="static" tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    {if $pageEntry && $userIsPageEntryAdmin}{$editPageEntry->render()}{/if}
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-12 col-lg-8">
            <div class="article-content">
                <textarea id="editor" name="article" class="form-control">{$article->content}</textarea>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            {$articleSeoChecklist->render()}
            {$articleMetadataEditor->render()}
            {$articleThumbnailEditor->render()}
            <div class="card mb-3">
                <div class="card-body">
                    <h3>Article Tags</h3>
                {$tagEditor->render()}
                <a class="description smaller" href="#" data-bs-toggle="modal"
                   data-bs-target="#about_categories_modal">
                    <i class="bi bi-info-circle-fill"></i> View article categories and their tags</a>
                </div>
            </div>
        </div>

    </div>

    <div class="navbar d-flex justify-content-center sticky-bottom mt-2">

        <div class="me-5 save-status-container">
            <p class="text-secondary align-center my-0 save-status-nochanges"><i
                        class="bi bi-file-earmark"></i> No Changes</p>
            <p class="text-primary align-center my-0 d-none save-status-saving"><i
                        class="bi bi-hourglass-split"></i> Saving</p>
            <p class="text-success align-center my-0 d-none save-status-saved"><i
                        class="bi bi-check-circle-fill"></i> Saved</p>
            <p class="text-danger align-center my-0 d-none save-status-error"><i
                        class="bi bi-exclamation-octagon-fill"></i> Save Unsuccessful</p>
        </div>

        {$articleStatusEditor->render()}
    </div>

</div>

<div class="modal fade" id="publisharticlemodal" tabindex="-1" aria-hidden="true"
        aria-labelledby="publisharticlemodallabel" data-articleid="">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title" id="publisharticlemodallabel">
                        Publish Article Confirmation
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="prompt">
                        Would you like to set the publish date of this article to be now, or leave it at its current
                        value
                        of <span class="current-datetime"> {$article->publishedTs|date_format:"%m/%d/%y %H:%M"} </span>
                        ?
                    </div>
                    <div class="publish-options row">
                        <div class="col-6">
                            <button value="publish-now" data-bs-dismiss="modal"
                                class="btn btn-sm  btn-outline-primary col-12 mt-2 article-state-btn publish-now-btn">
                                Publish to time set to now
                            </button>
                        </div>
                        <div class="col-6">
                            <button value="publish-ts" data-bs-dismiss="modal"
                                class="btn btn-sm  btn-outline-primary col-12 mt-2 article-state-btn publish-ts-btn">
                                Publish with time set to <span class="current-datetime">
                                    {$article->publishedTs|date_format:"%m/%d/%y %H:%M"} </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade tn-modal" id="about_categories_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">Article Categories and Tags</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Categories are more important tags. They have their own pages listing all articles within that
                    category. To add an article to a category, just use the correct tag:</p>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Category</th>
                        <th>Tag</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $categories as $category}
                        <tr>
                            <td>{$category->text}</td>
                            <td>{$category->tagText|lower}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {if $article->state === $statePublished}
        <div class="modal fade tn-modal" id="publishedwarningmodal" tabindex="-1" aria-hidden="true"
             aria-labelledby="publishedwarningmodal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-danger">
                        <h5 class="modal-title">Warning!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body text-danger">
                        This article is already published. Any changes you make will be made live on the website in real
                        time.

                        It is recommended that for edits beyond spelling or grammar corrections, you first revert the
                        article to draft status and re-publish after the edits are complete
                    </div>
                </div>
            </div>
        </div>
    {/if}
</div>