<div class="{$classAttribute} form-group mb-3" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    <label for="mainKeyword" class="form-label">domain.com/article/</label>
    <input {if !$canEdit || $article->state === $statePublished} disabled {/if}
            type="text" id="url_stub" name="mainKeyword" class="form-control"
            value="{$article->urlStub}">
</div>