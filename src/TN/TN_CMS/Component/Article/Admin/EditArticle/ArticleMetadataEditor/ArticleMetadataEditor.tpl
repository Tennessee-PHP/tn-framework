<div class="card mb-3 {$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    <div class="card-body">
        <h3>Metadata</h3>

        <form>
            {$articleUrlStubEditor->render()}
            <div class="row mb-3">
                <div class="w-100 ">
                    {$weekBySeasonSelect->render()}
                </div>
            </div>
            <div class="form-group mb-3">
                <label for="content_required_select" class="form-label">Content Required</label>
                <select id="content_required_select" class="form-select" name="content">
                    <option value="" {if $article->contentRequired == ""}selected{/if}>Free</option>
                    {foreach $contentOptions as $key => $content}
                        {if $content->key eq 'insider'}
                            <option value="{$content->key}"
                                    {if $article->contentRequired == $content->key}selected{/if}>{$content->name}</option>
                        {/if}
                    {/foreach}
                    {foreach $contentOptions as $key => $content}
                        {if $content->key neq 'insider'}
                            <option value="{$content->key}"
                                    {if $article->contentRequired == $content->key}selected{/if}>{$content->name}</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
            {if $canEdit}
                <div class="form-group">
                    <label for="article_weight_select" class="form-label">Article Weight</label>
                    <select id="article_weight_select" class="form-select" name="weight">
                        {for $i = 1 to 10}
                            <option value="{$i}" {if $article->weight == $i}selected{/if}>{$i}</option>
                        {/for}
                    </select>
                </div>
            {/if}
        </form>
    </div>
</div>