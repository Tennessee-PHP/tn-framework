<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Username</th>
                <th scope="col">Email</th>
                <th scope="col">Edit</th>
                <th scope="col">Login As</th>
            </tr>
            </thead>
            <tbody>
            {foreach $users as $user}
                <tr>
                    <td>
                        <a href="{$BASE_URL}staff/users/user/{$user->id}/profile">{$user->username}</a>

                        {if $user->inactive}<i class="bi bi-person-fill-x text-danger"></i>{/if}
                        {if $user->locked}<i class="bi bi-person-fill-lock text-warning"></i>{/if}
                    </td>
                    <td>{$user->email}</td>
                    <td>
                        <a class="btn btn-outline-primary btn-sm"
                           href="{$BASE_URL}staff/users/user/{$user->id}/profile"><i class="bi bi-pen-fill"></i>
                        </a>
                    </td>
                    <td>
                        <form method="POST" action="{$BASE_URL}staff/users/user/{$user->id}/login-as-user">
                            <input type="hidden" name="redirect_url" value="{$BASE_URL}">
                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-key-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            {/foreach}
            {if ! $users}
                <tr>
                    No Users Found
                </tr>
            {/if}
            </tbody>
        </table>
    </div>

    {$pagination->render()}

</div>