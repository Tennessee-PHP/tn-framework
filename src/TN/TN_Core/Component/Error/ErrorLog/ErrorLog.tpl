<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    {foreach $errors as $error}
        <div class="card my-4">
            <div class="card-header">
                <i class="bi bi-exclamation-circle-fill text-danger"></i>
                {$error->type} on {$error->file}:{$error->line}
            </div>
            <div class="card-body">
                <pre>{$error->message}</pre>
                <p>Occurred on {$error->timestamp|date_format:"%B %e, %Y %H:%I:%S"}</p>
            </div>
            <div class="card-footer">
                {$BASE_URL}{$error->path} for {if $error->userId > 0}{$error->username}{else}an anonymous user{/if}
            </div>
        </div>
        {foreachelse}
        <div class="alert alert-success">
            <h1>No Errors Found!</h1>
        </div>
    {/foreach}

    {$pagination->render()}

</div>