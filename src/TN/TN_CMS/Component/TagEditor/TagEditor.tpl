<div class="{$classAttribute}" id="{$idAttribute}" {*data-reload-url="{path route=$reloadRoute}"*}>

    <div class="form-group">
        <label for="main_keyword">Tags</label>
        <div class="tags">
            {foreach $taggedContents as $taggedContent}
                {include file="./Tag.tpl" text=$taggedContent->tag->text primary=$taggedContent->primary}
            {/foreach}
            <div class="dropdown">
                <input type="text" class="new-tag" name="new-tag" value="" maxlength="30" data-bs-toggle="dropdown"
                       aria-expanded="false">
                <ul class="dropdown-menu" id="tag_autocomplete_menu">
                </ul>
            </div>
        </div>
        <div class="d-none example-tag-container">
            {include file="./Tag.tpl" text="" primary=false}
        </div>
        <p class="form-text text-muted">Type a <b>comma (,)</b> after each tag.</p>
    </div>
</div>