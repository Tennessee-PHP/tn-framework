<div class="{$classAttribute}" id="{$idAttribute}">

    <section class="title-banner no-print landing-page-header landing-page-header-1 align-center"  {if !empty($landingPage->thumbnailSrc)} style="background-image:url('{$landingPage->thumbnailSrc}?w=2800');"{/if}>

        {if $pageEntry && $userIsPageEntryAdmin}
            <div class="container"><a class="btn btn-primary mt-5 mb-2 me-2 position-absolute" data-bs-toggle="modal"
                                      data-bs-target="#edit_page_entry_modal"><i class="bi bi-binoculars-fill"></i></a></div>

        {/if}

    </section>

    <div class="modal fade tn-modal" id="edit_page_entry_modal" data-bs-backdrop="static" tabindex="-1"
         aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                {if $pageEntry && $userIsPageEntryAdmin}{$editPageEntry->render()}{/if}
            </div>
        </div>
    </div>

    <section class="landing-page-main landing-page-main-1">
        <div class="container">

            <div class="card card-ps">

                <h1 class="landing-page-title">{$landingPage->title}</h1>

                {foreach $landingPage->contentParts as $content}
                    {$content}
                    {if !$content@last}
                        {include file="Component/Roadblock/Roadblock.tpl"}
                    {/if}
                {/foreach}

            </div>
        </div>
    </section>
</div>