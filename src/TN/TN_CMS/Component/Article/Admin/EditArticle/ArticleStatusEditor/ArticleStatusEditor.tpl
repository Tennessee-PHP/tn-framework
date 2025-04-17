<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}"
    data-article-id="{isset($article->id) ? $article->id : ''}">

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