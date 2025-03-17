<section class="{$classAttribute}" id="{$idAttribute}">

    <div class="row mb-3">
        <div class="col-12 col-md-4">
            <label for="{$listUsersTable->roleSelect->elementId}" class="form-label">Role</label>
            {$listUsersTable->roleSelect->render()}
        </div>
        <div class="col-12 col-md-4">
            <label for="username_search_field" class="form-label">Username</label>
            <input id="username_search_field" type="text" data-request-key="username" name="username" class="form-control" value="{$listUsersTable->username}">
        </div>
        <div class="col-12 col-md-4">
            <label for="email_search_field" class="form-label">Email</label>
            <input id="email_search_field" type="email" data-request-key="email" name="email" class="form-control" value="{$listUsersTable->email}">
        </div>
    </div>

    {$listUsersTable->render()}
</section>
