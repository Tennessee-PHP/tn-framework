<div class="{$classAttribute}" id="{$idAttribute}">
    <h2>Number of Items in Cache: {$cacheSize}</h2>

    <div class="alert alert-warning" role="alert">
        <p>
            <b>Warning!</b> this will temporarily affect site performance.
        </p>
        <p>
            <a href="{$BASE_URL}staff/storage/cache?clear_cache=true" class="btn btn-danger">Clear The Cache</a>
        </p>
    </div>
</div>