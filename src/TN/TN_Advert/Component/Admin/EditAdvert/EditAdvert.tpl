<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{$reloadRoute}" data-editor-type="{$editorType}">
    <form id="edit_advert" action="{path route='TN_Advert:Admin:saveAdvert' id=$advert->id ?? 'new'}" method="post">
        <input type="hidden" name="id" id="advert_id_field"
               value="{if $advert->id}{$advert->id}{else}new{/if}"/>

        <div class="row">
            <div class="col-12 form-group">
                <label for="advert_title">Title</label>
                <input type="text" placeholder="Title Of Your Advert..." class="form-control"
                       id="advert_title"
                       name="title" value="{$advert->title}" aria-describedby="advert_title_help"/>
                <small id="advert_title_help" class="form-text text-muted">This title isn't used anywhere in
                    the display of the advert. It's just used on the List Adverts page, so we have a way of
                    labelling
                    each advert.</small>
            </div>
        </div>


        <h2 class="mt-3">Advert Content</h2>

        <p class="help">To get the best display across different screen sizes, please consider avoiding
            images with text and instead using the Bootstrap elements to the right of the "B" button in the
            menu bar. The "jumbotron" elements in the Star menu are particularly useful for adverts, as of
            course are the button elements right next to the B.</p>

        <div class="row d-flex justify-content-center">
            <div class="col-12">
                <ul class="nav nav-tabs nav-underline" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {if $editorType === 'tinymce'}active{/if}" type="button" data-editor-tab="tinymce" role="tab" aria-controls="advert-editor-tinymce" aria-selected="{if $editorType === 'tinymce'}true{else}false{/if}">
                            TinyMCE
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {if $editorType === 'script'}active{/if}" type="button" data-editor-tab="script" role="tab" aria-controls="advert-editor-script" aria-selected="{if $editorType === 'script'}true{else}false{/if}">
                            Script
                        </button>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade {if $editorType === 'tinymce'}show active{/if}" id="advert-editor-tinymce" role="tabpanel" data-editor-panel="tinymce">
                        <div class="form-group">
                            <textarea id="editor" name="advert">
                              {if $advert->id}
                                  {$advert->advert}
                              {else}
                                  <div class="jumbotron my-3 advert-jumbotron">
                                  <h1 class="display-4">Advert Title</h1>
                                  <p class="lead">This is the main text of the advert.</p>
                                  <hr class="my-4" />
                                  <p>This might be the secondary call to action.</p>
                                  <a class="btn btn-primary btn-lg" href="#">Edit this CTA from the button menu</a></div>
                              {/if}                            </textarea>
                        </div>
                    </div>
                    <div class="tab-pane fade {if $editorType === 'script'}show active{/if}" id="advert-editor-script" role="tabpanel" data-editor-panel="script">
                        <div class="form-group">
                            <textarea id="script_editor" class="form-control font-monospace" rows="15" data-editor-textarea="script">{$scriptContent|escape:'htmlall'}</textarea>
                            <small class="form-text text-muted mt-2">To insert raw HTML as an advert, please use the textarea below. Content is stored exactly as provided.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mt-3">Audience &amp; Visibility</h2>
        <div class="row g-2">

            <div class="col-6 col-md-2 form-group">
                <label for="audience">Audience</label>
                <select class="form-control" id="audience" name="audience"
                        aria-describedby="audience_help">
                    {foreach $audienceOptions as $option}
                        <option value="{$option@key}"{if $advert->audience === $option@key} selected{/if}>{$option}</option>
                    {/foreach}
                </select>
                <small id="audience_help" class="form-text text-muted">The group of visitors/users who will
                    see the advert.</small>
            </div>

            <div class="col-6 col-md-2 form-group">
                <div class="form-check clearfix">
                    <input type="checkbox" class="form-check-input" id="enabled" name="enabled" value="1"
                           aria-describedby="enabled_help"{if $advert->enabled} checked{/if}/>
                    <label class="form-check-label" for="enabled">Enabled</label>
                </div>
                <small id="enabled_help" class="form-text text-muted">If this isn't checked, the advert
                    will never show.</small>
            </div>

            <div class="col-6 col-md-3 form-group">
                <label for="start_date">Start Date</label>
                <input type="date" class="form-control" id="start_date"
                       name="startTs" value="{$advert->startTs|date_format:'%Y-%m-%d'}"
                       aria-describedby="start_date_help"/>
                <small id="start_date_help" class="form-text text-muted">The message will start showing at
                    the beginning of this day.</small>
            </div>

            <div class="col-6 col-md-3 form-group">
                <label for="end_date">End Date</label>
                <input type="date" class="form-control" id="end_date"
                       name="endTs" value="{$advert->endTs|date_format:'%Y-%m-%d'}"
                       aria-describedby="end_date_help"/>
                <small id="end_date_help" class="form-text text-muted">The message will stop showing at the
                    beginning of this day.</small>
            </div>

            <div class="col-6 col-md-2  form-group">
                <label for="advert_weight_select">Advert Weight</label>
                <select id="advert_weight_select" class="form-control" name="weight">
                    {for $i = 1 to 10}
                        <option value="{$i}" {if $advert->weight == $i}selected{/if}>{$i}</option>
                    {/for}
                </select>
                <small id="advert_weight_select_help" class="form-text text-muted">The priority level of the
                    created advert.</small>
            </div>

            <div class="col-sm-3 form-group">
                <label for="advert_frequency_select">Display Frequency</label>
                <select id="advert_frequency_select" name="displayFrequency" class="form-control">
                    {$frequencyOptions}
                    {foreach $frequencyOptions as $key => $option}
                        <option value="{$key}"
                                {if $advert->displayFrequency === $key}selected {/if}>
                            {$option}</option>
                    {/foreach}
                </select>
                <small id="advert_frequency_select_help" class="form-text text-muted">Set display frequency
                    of adverts - currently only applies to SITE MESSAGES</small>
            </div>


        </div>
        <h2 class="mt-3">Locations</h2>

        <div class="row">
            {foreach from=$groupedOptions key=sizeType item=options}
                <div class="col-12">
                    <h3>{$sizeType}</h3>
                </div>
                {foreach from=$options item=option}
                    {assign 'optionKey' $option@key}
                    <div class="col-12 col-md-6 col-lg-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input location-option"
                                           id="location_{$option.key}"
                                           name="{$option.key}" value="1"
                                           aria-describedby="location_{$option.key}_help"
                                            {foreach from=$advertPlacements item=advertPlacement}
                                                {if $advertPlacement->spotKey === $option.key}checked{/if}
                                            {/foreach} />
                                    <label class="form-check-label"
                                           for="location_{$option.key}">{$option.name}</label>
                                </div>
                                <small id="location_{$optionKey}_help" class="form-text text-muted">
                                    {$option.description}
                                </small>
                            </div>
                        </div>
                    </div>
                {/foreach}
            {/foreach}
        </div>
    </form>

    <table class="table table-striped mt-3">
        <thead>
        <tr>
            <th>Day</th>
            <th>Unique Impressions</th>
            <th>Total Impressions</th>
            <th>Unique Clicks</th>
            <th>Total Clicks</th>
            <th>Click Through Rate</th>
        </tr>
        </thead>
        <tbody>
        {foreach $advertStats as $stats}
            <tr>
                <td>{$stats->dayStartTs|date_format:"%B %e, %Y"}</td>
                <td>{$stats->uniqueImpressions}</td>
                <td>{$stats->totalImpressions}</td>
                <td>{$stats->uniqueClicks}</td>
                <td>{$stats->totalClicks}</td>
                <td>{if $stats->totalImpressions != 0}{$stats->totalClicks/$stats->totalImpressions}{else}N/A{/if}</td>
            </tr>
        {/foreach}
        <tr>
            <td><strong>Total</strong></td>
            <td>
                <strong>{if $aggregateStats.totalUniqueImpressions}{$aggregateStats.totalUniqueImpressions}{else}0{/if}</strong>
            </td>
            <td>
                <strong>{if $aggregateStats.totalImpressions}{$aggregateStats.totalImpressions}{else}0{/if}</strong>
            </td>
            <td>
                <strong>{if $aggregateStats.totalUniqueClicks}{$aggregateStats.totalUniqueClicks}{else}0{/if}</strong>
            </td>
            <td><strong>{if $aggregateStats.totalClicks}{$aggregateStats.totalClicks}{else}0{/if}</strong>
            </td>
            <td>
                <strong>{if $aggregateStats.totalImpressions != 0}{$aggregateStats.formattedClickThroughRate} {else}N/A{/if}</strong>
            </td>
        </tr>
        </tbody>
    </table>

    <div class="navbar d-flex justify-content-center sticky-bottom">
        <button type="button" class="btn btn-primary" id="save_advert_button">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            Save Advert
        </button>
    </div>
</div>