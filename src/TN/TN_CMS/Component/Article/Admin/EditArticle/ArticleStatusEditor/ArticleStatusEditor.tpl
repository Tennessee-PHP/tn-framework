<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}"
     data-article-id="{isset($article->id) ? $article->id : ''}">

    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Article Status" 
        message="Loading Article Status..."
    }

    <div class="card">
        <div class="component-body d-flex align-items-center">
            <div class="component-loading d-none d-flex align-items-center justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>

            {if $article->state === $stateDraft}
                <span class="me-2">This article is currently a <strong>Draft</strong></span>
                <button value="editor" class="btn btn-sm btn-primary me-2 article-state-btn editor-btn">
                    <i class="bi bi-file-earmark-medical"></i> Send to Editor
                </button>
                <a class="btn btn-sm btn-outline-primary article-preview-link me-2" target="_blank"
                   href="{$BASE_URL}{$article->url}?preview=1">
                    <i class="bi bi-eye"></i> Preview
                </a>
                {if $canSetToTemplate}
                    <button value="template" class="btn btn-sm btn-outline-info me-2 article-state-btn template-btn">
                        <i class="bi bi-file-earmark-font"></i> Make Template
                    </button>
                {/if}
            {elseif $article->state === $stateReadyForEditing}
                <span class="me-2">This article is <strong>Ready for Editing</strong></span>
                <button value="draft" class="btn btn-sm btn-danger article-state-btn draft-btn me-2">
                    <i class="bi bi-file-earmark-person"></i> Back to Author
                </button>
                <a class="btn btn-sm btn-outline-primary article-preview-link me-2" target="_blank"
                   href="{$BASE_URL}{$article->url}?preview=1">
                    <i class="bi bi-eye"></i> Preview
                </a>
                {if $canSetToTemplate}
                    <button value="publish" data-bs-toggle="modal" data-bs-target="#publisharticlemodal"
                            class="btn btn-sm me-2 btn-primary article-state-btn publish-btn">
                        <i class="bi bi-file-earmark-check"></i>Publish
                    </button>
                    <button value="template" class="btn btn-sm btn-info article-state-btn template-btn me-2">
                        <i class="bi bi-file-earmark-font"></i>Make Template
                    </button>
                {/if}
            {elseif $article->state === $statePublished}
                <span class="me-2">This article is currently <strong>Published</strong></span>
                <button value="editor" class="btn btn-sm  btn-danger me-2 article-state-btn editor-btn">
                    <i class="bi bi-file-earmark-excel"></i> Un-Publish
                </button>
                <a class="btn btn-sm btn-outline-primary article-preview-link me-2" target="_blank"
                   href="{$BASE_URL}{$article->url}?preview=1"><i class="bi bi-eye"></i>Preview
                </a>
            {elseif $article->state === $stateTemplate}
                <span class="me-2">This article is a <strong>Template</strong></span>
                {if $canSetToTemplate}
                    <button value="draft" class="btn btn-sm  btn-outline-secondary me-2 article-state-btn draft-btn">
                        <i class="bi bi-file-earmark"></i> Make Normal
                    </button>
                {/if}
            {/if}
        </div>
    </div>
</div>

<div class="modal fade" id="publisharticlemodal" tabindex="-1" aria-hidden="true"
     aria-labelledby="publisharticlemodal" data-articleid="">
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
                    of <span class="current-datetime">  {$article->publishedTs|date_format:"%m/%d/%y %H:%M"} </span> ?
                </div>
                <div class="publish-options row">
                    <div class="col-6">
                        <button value="publish-now"
                                data-bs-dismiss="modal"
                                class="btn btn-sm  btn-outline-primary col-12 mt-2 article-state-btn publish-now-btn">
                            Publish to time set to now
                        </button>
                    </div>
                    <div class="col-6">
                        <button value="publish-ts"
                                data-bs-dismiss="modal"
                                class="btn btn-sm  btn-outline-primary col-12 mt-2 article-state-btn publish-ts-btn">
                            Publish with time set to <span
                                    class="current-datetime"> {$article->publishedTs|date_format:"%m/%d/%y %H:%M"} </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>