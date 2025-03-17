<div class="{$classAttribute}" id="{$idAttribute}">
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Key</th>
                <th scope="col">Edit</th>
            </tr>
            </thead>
            <tbody>
            {foreach $templates as $template}
                <tr>
                    <td>{$template->name}</td>
                    <td>{$template->key}</td>
                    <td><a href="{$BASE_URL}staff/emails/edit/{$template->key|replace:'/':'-'}"
                           class="btn btn-primary fs-6 text">Edit</a></td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>