<div class="{$classAttribute}" id="{$idAttribute}" data-landingpageid="{$landingPage->editId}">
    {strip}
        <section
                class="title-banner no-print landing-page-header landing-page-header-1" {if !empty($landingPage->thumbnailSrc)} style="background-image:url('{$landingPage->thumbnailSrc}');"{/if}>


        </section>

        <section class="landing-page-main landing-page-main-1">
            <div class="container">
                <div class="card card-ps">
                    <input class="landing-page-title" type="text"
                           value="{$landingPage->title}" placeholder="Enter your landing page title..."/>

                    <textarea id="editor" name="landingPage" class="form-control">{$landingPage->content}</textarea>
                </div>
            </div>

        </section>
        <div class="navbar sticky sticky-bottom d-flex justify-content-center">
            <a class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#landing_page_options_modal"><i
                        class="bi bi-gear-wide-connected"></i> Options</a>
            <button class="btn btn-primary" id="save_landing_page_btn" disabled>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <i class="bi bi-floppy2-fill"></i> Save
            </button>
        </div>
    {/strip}

    <div class="modal fade tn-modal" id="landing_page_options_modal" tabindex="-1" aria-hidden="true"
         aria-labelledby="landing_page_options_modal_title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="landing_page_options_modal_title">Landing Page Options</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="landing_page_url" class="form-label">URL (domain.com/...)</label>
                        <input type="text" class="form-control" id="landing_page_url" name="urlStub"
                               value="{$landingPage->urlStub}"/>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value=""
                               id="allow_removed_navigation" {if $landingPage->allowRemovedNavigation} checked{/if}>
                        <label class="form-check-label" for="allow_removed_navigation">
                            Remove site navigation for visitors who are not signed in, and who came to this page from
                            outside of the website
                        </label>
                    </div>
                    <div class="mb-3">
                        <label for="landing_page_state" class="form-label">State</label>
                        <select class="form-control" id="landing_page_state" name="state">
                            {foreach $stateOptions as $stateOption}
                                <option value="{$stateOption.value}"
                                        {if $stateOption.value === $landingPage->state}selected{/if}>{$stateOption.label}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="mb-2">
                        <form id="landing_page_image_form" method="POST" enctype="multipart/form-data">
                            <label for="landing_page_image" class="form-label">Header Image</label>
                            <input class="form-control" accept="image/png,image/jpeg,image/img" type="file"
                                   id="landing_page_image" name="image">
                        </form>
                        <input type="hidden" id="landing_page_thumbnail_src" name="thumbnailSrc"
                               value="{$landingPage->thumbnailSrc}"/>
                    </div>
                    <div class="mb-3">
                        <label for="landing_page_convertkit_tag" class="form-label">ConvertKit Tag</label>
                        <input type="text" class="form-control" id="landing_page_convertkit_tag" name="convertKitTag"
                               value="{$landingPage->convertKitTag}"/>
                    </div>
                    <div class="mb-3">
                        <label for="landing_page_campaign" class="form-label">Sales Campaign</label>
                        <select class="form-control" id="landing_page_campaign" name="campaignId">
                            <option value="0">-- None --</option>
                            {foreach $campaignOptions as $campaign}
                                <option value="{$campaign->id}"
                                        {if $campaign->value === $landingPage->campaignId}selected{/if}>{$campaign->key}</option>
                            {/foreach}
                        </select>
                    </div>
                    {$tagEditor->render()}
                </div>
            </div>
        </div>
    </div>
</div>