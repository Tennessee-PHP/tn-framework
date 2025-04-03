<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Landing Pages" 
        message="Loading Landing Pages..."
    }

    <div class="d-flex flex-md-row flex-column">
        <a href="{$BASE_URL}staff/landing-pages/edit" class="btn btn-primary mb-3 me-0 me-md-3">Add
            Landing Page</a>
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
                    <th>Title</th>
                    <th>URL</th>
                    <th>State</th>
                    <th>Content</th>
                    <th>#Tags</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                {foreach $landingPages as $landingPage}
                    <tr data-landingpageid="{$landingPage->id}" data-landingpagetitle="{$landingPage->title}">
                        <td>
                            <a href="{$BASE_URL}staff/landing-pages/edit?landingpageid={$landingPage->id}"
                                class="text-decoration-none text-dark">{$landingPage->title}</a>
                        </td>
                        <td>{$BASE_URL}{$landingPage->getUrl()}</td>
                        <td>
                            {if $landingPage->state === $stateDraft}
                                Draft
                            {elseif $landingPage->state === $statePublished}
                                Published
                            {/if}
                        </td>
                        <td>{if $landingPage->contentRequired}{$landingPage->contentRequired}
                            {elseif !$landingPage->contentRequired}
                            free{/if}
                        </td>
                        <td>{$landingPage->numTags}</td>
                        <td>
                            <a href="{$BASE_URL}staff/landing-pages/edit?landingpageid={$landingPage->id}"
                                class="btn btn-sm btn-secondary"><i class="bi bi-pencil-fill"></i< /a>
                        </td>
                        <td>
                            <a data-bs-toggle="modal" data-bs-target="#deletelandingpagemodal"
                                class="btn btn-sm btn-danger delete d-flex justify-content-center"><i
                                    class="bi bi-trash-fill"></i></a>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    {$pagination->render()}

    <div class="modal fade" id="deletelandingpagemodal" tabindex="-1" aria-hidden="true"
        aria-labelledby="deletelandingpagemodalabel" data-landingpageid="">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title" id="deletelandingpagemodallabel">
                        Confirm Landing Page Deletion
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you wish to delete the landing page "<span class="landingpage-title"></span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger delete-confirm" data-bs-dismiss="modal">Yes,
                        Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>