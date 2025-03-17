<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    <div class="row mb-3">
        <div class="col-12 col-md-4">
            <label for="title_search_field">Title:</label>
            <input id="title_search_field" type="text" data-request-key="title" name="title" class="form-control" placeholder="Filter by title..." value="{$title}">
        </div>
        <div class="col-12 col-md-4">
            <a href="{path route='TN_Advert:Admin:editAdvert' id='new'}" class="btn btn-primary mt-4">Add Advert</a>
        </div>
    </div>

    <table class="table table-responsive">
        <thead>
        <tr>
            <th>
                Title
            </th>
            <th>Audience</th>
            <th>Enabled</th>
            <th>Show Between</th>
            <th>7-day Total Impressions</th>
            <th>7-day Total Clicks</th>
            <th>7-day CTR</th>
            <th>Edit</th>
            <th>Delete</th>
        </tr>
        </thead>
        <tbody>
        {foreach $adverts as $advert}
            <tr>
                <td><a href="{path route='TN_Advert:Admin:editAdvert' id=$advert->id}">{$advert->title}</a></td>
                <td>{$advert->audience}</td>
                <td class="table-{if $advert->enabled}success{else}danger{/if}">{if $advert->enabled}yes{else}no{/if}</td>
                <td>
                    {if $advert->startTs === 0 && $advert->endTs === 0} Always{/if}
                    {if $advert->startTs > 0} from {$advert->startTs|date_format:"%B %e, %Y"}{/if}
                    {if $advertTs->endTs > 0} until {$advert->endTs|date_format:"%B %e, %Y"}{/if}</td>
                {foreach $totalStats as $contentId => $stats}
                    {if $contentId == $advert->id}
                        <td>{$stats.totalImpressions}</td>
                        <td>{$stats.totalClicks}</td>
                        <td>{if $stats.totalImpressions != 0}{$stats.CTR|default:"N/A"}{/if}</td>
                        {break}
                    {/if}
                {/foreach}
                {if !$totalStats[$advert->id]}
                    <td>0</td>
                    <td>0</td>
                    <td>N/A</td>
                {/if}
                <td>
                    <a href="{path route='TN_Advert:Admin:editAdvert' id=$advert->id}" class="btn btn-sm btn-secondary"><i class="bi bi-pencil-fill"></i></a>
                </td>
                <td>
                    <a data-bs-toggle="modal" data-bs-target="#delete_advert_modal" data-advert-title="{$advert->title}" data-advert-id="{$advert->id}" class="btn btn-sm btn-danger"><i class="bi bi-trash-fill"></i></a>
                </td>
            </tr>
        {/foreach}



        </tbody>
    </table>
    {$pagination->render()}

    <div class="modal fade"
         id="delete_advert_modal"
         tabindex="-1"
         aria-hidden="true"
         aria-labelledby="delete_advert_modal_label">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                <span class="modal-title"
                      id="delete_advert_modal_label">
                    Confirm Advert Deletion
                </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you wish to delete the advert "<span class="advert-title">title</span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-advert-id="">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>


</div>
