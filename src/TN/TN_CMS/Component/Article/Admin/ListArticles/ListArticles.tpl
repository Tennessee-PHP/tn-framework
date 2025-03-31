<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Articles" 
        message="Loading Articles..."
    }

    <p>Control which articles are displayed across the website.</p>
    {if $articleListing->isArticleEditor || $articleListing->isArticleAuthor}
        <div class="d-flex flex-md-row flex-column">
            <a href="{$BASE_URL}staff/articles/edit?" class="btn btn-primary mb-3 me-0 me-md-3">Add
                Article From
                Scratch</a>
            <a data-bs-toggle="modal" data-bs-target="#fromtemplatearticlemodal" class="btn btn-primary template mb-3">Add
                New Article From Template</a>
        </div>
    {/if}

    <div class="row mb-3">

        <div class="col col-12 col-md-6 col-lg-4">
            {$articleStateSelect->render()}
        </div>

        {if $isArticleEditor}
            <div class="col col-12 col-md-6 col-lg-4">
                {$userSelect->render()}
            </div>
        {/if}
    </div>

    <div class="component-loading d-none d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only"></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Author</th>
                    <th>Title</th>
                    <th>State</th>
                    <th>
                        <a class="reload-sortable-col-link text-decoration-none text-dark" href="#"
                            data-sortProperty="weight">
                            Weight
                            {if $sortProperty === 'weight' && $sortDirection === SORT_ASC}<i
                                class="bi bi-sort-up"></i>{/if}
                            {if $sortProperty === 'weight' && $sortDirection !== SORT_ASC}
                                <i class="bi bi-sort-down"></i>
                            {/if}
                        </a>
                    </th>
                    <th>Content</th>
                    <th>
                        <a class="reload-sortable-col-link publishedTs-sort text-decoration-none text-dark" href="#"
                            data-sortProperty="publishedTs">Publish Date
                            {if $sortProperty === 'publishedTs' && $sortDirection === SORT_ASC}
                                <i class="bi bi-sort-up"></i>
                            {/if}
                            {if $sortProperty === 'publishedTs' && $sortDirection !== SORT_ASC}
                                <i class="bi bi-sort-down"></i>
                            {/if}</a>
                    </th>
                    <th>#Tags</th>
                    <th>Preview</th>
                    {if !$isBackendArticleListViewer}
                        <th>Edit</th>
                        <th>Delete</th>
                    {/if}
                </tr>
            </thead>
            <tbody>
                {foreach $articles as $article}
                    <tr data-articleid="{$article->id}" data-articletitle="{$article->title}">
                        <td>{if $article->authorId !== 0}{$article->authorName}{else}Staff{/if}</td>
                        <td>
                            {if $isArticleEditor || $article->state !== $statePublished}
                                <a href="{$BASE_URL}staff/articles/edit?articleid={$article->id}"
                                    class="text-decoration-none text-dark">{$article->title}</a>
                            {elseif !$sArticleEditor && $article->state === $statePublished}
                                <a data-bs-toggle="modal" data-bs-target="#publishedwarningmodal" href="#"
                                    class="text-decoration-none text-dark publish">{$article->title}</a>
                            {/if}
                        </td>
                        <td>
                            {if $article->state === $stateDraft}
                                Draft
                            {elseif $article->state === $stateReadyForEditing}
                                Ready for Editing
                            {elseif $article->state === $statePublished}
                                Published
                            {elseif $article->state === $stateTemplate}
                                Template
                            {/if}
                        </td>
                        <td>
                            <select id="article_weight_select" class="weight py-1 form-select" name="weight">
                                {for $i = 10 to 0 step -1}
                                    <option value="{$i}" {if $article->weight == $i}selected{/if}>{$i}</option>
                                {/for}
                            </select>
                        </td>
                        <td>{if $article->contentRequired}{$article->contentRequired} 
                        {elseif !$article->contentRequired}
                            free{/if}
                        </td>
                        <td>{$article->publishedTs|date_format:"%m/%d/%y %H:%M"}</td>
                        <td>{$article->numTags}</td>
                        <td>{if $article->urlStub}<a href="{$BASE_URL}article/{$article->urlStub}?preview=1"
                                    class="btn btn-primary btn-sm" target="_blank"><i class="bi bi-eye"></i>
                            </a>{/if}</td>
                        {if !$isBackendArticleListViewer}
                            <td>
                                <a href="{$BASE_URL}staff/articles/edit?articleid={$article->id}"
                                    class="btn btn-sm btn-secondary"><i class="bi bi-pencil-fill"></i< /a>
                            </td>
                            <td>
                                <a data-bs-toggle="modal" data-bs-target="#deletearticlemodal"
                                    class="btn btn-sm btn-danger delete d-flex justify-content-center"><i
                                        class="bi bi-trash-fill"></i></a>
                            </td>
                        {/if}
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    {$pagination->render()}

    <div class="modal fade" id="deletearticlemodal" tabindex="-1" aria-hidden="true"
        aria-labelledby="deletearticlemodalabel" data-articleid="">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title" id="deletearticlemodallabel">
                        Confirm Article Deletion
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you wish to delete the article "<span class="article-title"></span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger delete-confirm" data-bs-dismiss="modal">Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="fromtemplatearticlemodal" tabindex="-1" aria-hidden="true"
        aria-labelledby="fromtemplatearticlemodal" data-articleid="">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title" id="fromtemplatearticlemodallabel">
                        Select Template
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {foreach $articleListing->templates as $template}
                        {if $template->state === $stateTemplate}
                            <div class="container">
                                <div class="row">
                                    <div class="col border-bottom border-1 border-secondary py-1">
                                        <div class="d-flex align-items-center">
                                            <div class="col-6">
                                                {$template->title}
                                            </div>
                                            <div class="ms-3 col-4">
                                                <a href="{$BASE_URL}/staff/articles/edit?fromtemplateid={$template->id}"
                                                    class="btn btn-sm btn-secondary-outline">Use This Template</a>
                                            </div>
                                            <div class="ms-3 col-2">
                                                <a href="{$BASE_URL}staff/articles/edit?articleid={$template->id}"
                                                    class="btn btn-sm btn-secondary-outline"><i
                                                        class="bi bi-pencil-fill"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
</div>