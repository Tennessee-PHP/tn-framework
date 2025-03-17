<div class="{$classAttribute}" id="{$idAttribute}">
    <form id="edit_campaign">
        <input type="hidden" {if $isPhantom}name="id" value="{$campaign->id}"{/if}>

        <div class="row my-3">
            <div class="col-12 col-md-6 d-flex flex-column">
                <label for="zeditcampaignkey_field">Campaign Key</label>
                <input type="text" id="editcampaignkey_field" name="key" class="form-control"
                       {if $isPhantom}value="{$campaign->key}"{/if}
                       aria-describedby="editcampaignkey_help">
            </div>
            <div class="col12 col-md-6 left-side-labeled">
                <small id="editcampaignkey_help" class="form-text text-muted">The key for a campaign is part
                    of the URL prospects are sent to</small>
            </div>
        </div>

        <div class="row my-3">
            <div class="col-12 col-md-6">
                <input type="checkbox" id="editcampaignusebaseurl_field" name="useBaseUrl"
                       {if $isPhantom}{if $campaign->useBaseUrl}Checked{/if}{/if}  class="form-check"
                       aria-describedby="editcampaignusebaseurl_help" value="1">
                <label for="editcampaignusebaseurl_field" class="form-label">Use Read-out URL</label>
            </div>
            <div class="col12 col-md-6">
                <small id="editcampaignusebaseurl_help" class="form-text text-muted">
                    If you use a read-out URL, the campaign's link will be in the format
                    domain.com/campaign. This should only be used where there's a significant
                    advantage, e.g. podcast reads, over the alternative
                    (domain.com/welcome/campaign)
                </small>
            </div>
        </div>

        <div class="row my-3 d-none">
            <div class="col-12 col-md-6 d-flex flex-column">
                <label for="editfunnelkey_field" class="form-label">Funnel Name</label>
                <select name="funnelKey" id="editfunnelkey_field"
                        aria-describedby="editfunnelkey_help" class="form-control">
                    {foreach $funnels as $funnel}
                        <option
                                {if $isPhantom && $funnel->key === $campaign->funnelKey}selected{/if}
                                value="{$funnel->key}">{$funnel->name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col12 col-md-6 left-side-labeled">
                <small id="editfunnelkey_help" class="form-text text-muted">
                    For now, the only funnel available on the website is the subscription sales funnel. We
                    may add more in the future - e.g. email signup funnel.
                </small>
            </div>

        </div>

        <div class="row my-3">
            <div class="col-12 col-md-6 d-flex flex-column">
                <label for="editfunnelentrystage_field" class="form-label">Start at Route</label>
                <select name="funnelEntryStage" id="editfunnelentrystage_field" class="form-control"
                        aria-describedby="editfunnelentrystage_help">
                    {foreach $funnels[0]->stages as $stage}
                        <option
                                {if $isPhantom && $stage@key === $campaign->funnelEntryStage}selected{/if}
                                value="{$stage@key}">{$BASE_URL}{$funnels[0]->getStageRoute($stage@key + 1)}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col12 col-md-6 left-side-labeled">
                <small id="editfunnelentrystage_help" class="form-text text-muted">
                    Users entering this campaign will begin at this route. Warning!: later routes may have
                    required earlier user changes in the user interface. Test the campaign if you select
                    anything other than the first route.
                </small>
            </div>
        </div>

        <div class="row my-3">
            <div class="col-12 col-md-6 d-flex flex-column">
                <label for="editcampaignvouchercodeID_field" class="form-label">Promo Code</label>
                <select name="voucherCodeId" id="editcampaignvouchercodeID_field" class="form-control"
                        aria-describedby="editcampaignvouchercodeID_help">
                    <option value="">No Promo Code</option>
                    {foreach $vouchers as $v}
                        <option value="{$v->id}"
                                {if $isPhantom}{if $v->id === $campaign->voucherCodeId}selected{/if}{/if}>{$v->code}</option>
                    {/foreach}
                </select>
            </div>

            <div class="col-4 d-flex flex-column left-side-labeled">

                <small id="editcampignvouchercodeID_help" class="form-text text-muted">Optionally, add a
                    promo code to be automatically granted to any prospect of this campaign</small>

            </div>

        </div>

        <div class="row my-3">
            <div class="col-12 col-md-6 d-flex flex-column">
                <label for="editcampaignvouchercodeNotes_field" class="form-label">Notes</label>
                <textarea name="notes" id="editcampaignvouchercodeNotes_field" class="form-control"
                          aria-describedby="editcampaignvouchercodeNotes_help">
                                 {if $isPhantom}{$campaign->notes}{/if}
                            </textarea>
            </div>

            <div class="col-4 d-flex justify-content-center flex-column left-side-labeled">

                <small id="editcampignvouchercodeID_help" class="form-text text-muted">Add a descriptive
                    note
                    about this campaign. This will show up as a tooltip on the list of campaigns.</small>

            </div>

        </div>

        <div class="text-center mt-5">
            <input type="submit" class="btn btn-primary" value="Save Campaign">
            <div id="edit-loading" style="text-align:center; display:none;">
                <div class="spinner-border" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>
        </div>
    </form>
</div>