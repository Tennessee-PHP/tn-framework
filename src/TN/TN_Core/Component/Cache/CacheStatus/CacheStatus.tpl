<div class="{$classAttribute}" id="{$idAttribute}">
    <h2>Number of Items in Cache: {$cacheSize}</h2>

    <div class="alert alert-warning" role="alert">
        <p>
            <b>Warning!</b> this will temporarily affect site performance.
        </p>
        <p>
            <form action="{path route='TN_Core:Cache:clearCache'}" method="post">
                {if $csrfToken}<input type="hidden" name="_csrf" value="{$csrfToken|escape:'html'}">{/if}
                <button type="submit" class="btn btn-danger">Clear The Cache</button>
            </form>
        </p>
    </div>
</div>