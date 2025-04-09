<div class="{$classAttribute}" id="{$idAttribute}">
    <div class="row">

        <div class="col col-12 col-md-6">
            {if $observerIsSuperUser}
                <div class="form-group mb-3">
                    <label class="form-label">User ID</label>
                    <input type="text" class="form-control" value="{$user->id}" disabled>
                </div>
            {/if}


            <form id="edit_email_form"
                  action="{path route="TN_Core:User:userProfileEditUserTabSaveField" username=$username}">
                <div class="form-group mb-3">
                    <label for="field_email" class="form-label">Email Address</label>
                    <div class="d-flex">
                        <input type="email" id="field_email" class="form-control flex-fill" name="email"
                               value="{$user->email}">
                        <button type="submit" class="btn btn-outline-primary ms-3 d-flex align-items-center">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                  aria-hidden="true"></span>
                            <i class="bi bi-check-circle me-1 d-none"></i>
                            Save
                        </button>
                    </div>
                </div>
            </form>

            <form id="edit_name_form"
                  action="{path route="TN_Core:User:userProfileEditUserTabSaveField" username=$username}">
                <div class="form-group mb-3">
                    <label for="field_first" class="form-label">Name</label>
                    <div class="d-flex">
                        <input type="text" id="field_first" class="form-control flex-fill me-2" name="first"
                               value="{$user->first}">
                        <input type="text" class="form-control flex-fill" name="last" value="{$user->last}">
                        <button type="submit" class="btn btn-outline-primary ms-3 d-flex align-items-center">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                  aria-hidden="true"></span>
                            <i class="bi bi-check-circle me-1 d-none"></i>
                            Save
                        </button>
                    </div>
                </div>
            </form>

        </div>
        <div class="col col-12 col-md-6">

            {if $observerIsSuperUser}
                <form id="edit_username_form"
                      action="{path route="TN_Core:User:userProfileEditUserTabSaveField" username=$username}">
                    <div class="form-group mb-3">
                        <label for="field_username" class="form-label">Username</label>
                        <div class="d-flex">
                            <input type="email" id="field_username" class="form-control flex-fill" name="username"
                                   value="{$user->username}">
                            <button type="submit" class="btn btn-outline-primary ms-3 d-flex align-items-center">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                  aria-hidden="true"></span>
                                <i class="bi bi-check-circle me-1 d-none"></i>
                                Save
                            </button>
                        </div>
                    </div>
                </form>
            {/if}

            <form id="edit_password_form"
                  action="{path route="TN_Core:User:userProfileEditUserTabSaveField" username=$username}">
                <div class="form-group mb-3">
                    {if !$observerIsSuperUser}
                        <label for="field_current_password" class="form-label">Current Password</label>
                        <div class="d-flex mb-2">
                            <input type="password" id="field_current_password" class="form-control flex-fill me-2"
                                   name="currentPassword" value="">
                        </div>
                    {/if}

                    <label for="field_password" class="form-label">New Password</label>
                    <div class="d-flex mb-2">
                        <input type="password" id="field_password" class="form-control flex-fill me-2" name="password"
                               value="">
                    </div>

                    <label for="field_password_repeat" class="form-label">Confirm New Password</label>
                    <div class="d-flex">
                        <input type="password" id="field_password_repeat" class="form-control flex-fill me-2"
                               name="passwordRepeat" value="">
                        <button type="submit" class="btn btn-outline-primary ms-3 d-flex align-items-center">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                  aria-hidden="true"></span>
                            <i class="bi bi-check-circle me-1 d-none"></i>
                            Save
                        </button>
                    </div>
                </div>
            </form>

            {if $observerIsSuperUser}
                <button type="button" id="generate_password" class="btn btn-outline-secondary ms-3 d-flex align-items-center">
                    Generate Password
                </button>
            {/if}

        </div>
    </div>

    {if $observerIsSuperUser}
        <h3 class="mt-3">Activation Status</h3>

        {if $user->active}{else}{/if}
        <form id="user_active_change_form"
              action="{path route="TN_Core:User:userProfileEditUserTabInactiveChange" username=$username}">
            <div class="d-flex">
                <a href="{path route="TN_Core:User:listUsers"}" target="_blank"
                   class="btn btn-outline-primary flex-fill">Find&nbsp;User&nbsp;ID&nbsp;To&nbsp;Merge</a>
                <input type="text" id="field_merge_id" class="form-control mx-2 flex-fill"
                       name="secondaryUserId" value="">
                <button type="submit" class="btn btn-outline-primary ms-3 d-flex align-items-center">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                  aria-hidden="true"></span>
                    <i class="bi bi-check-circle me-1 d-none"></i>
                    Merge&nbsp;Users
                </button>
            </div>
        </form>

        <div class="table-responsive mt-3">
            <table class="table">
                <thead>
                <tr>
                    <th>Date of Change</th>
                    <th>User Status</th>
                    <th>Comment</th>
                    <th>Done By</th>
                </tr>
                </thead>
                <tbody>
                {foreach $userInactiveChanges as $userInactiveChange}
                    <tr>
                        <td>{$userInactiveChange->ts|date_format:"%B %e, %Y %H:%I:%S"}</td>
                        <td class="table-{if $userInactiveChange->active}success{else}danger{/if}">{if $userInactiveChange->active}Active{else}Inactive{/if}</td>
                        <td>{$userInactiveChange->comment}</td>
                        <td>{if isset($userInactiveChange->byUser)}{$userInactiveChange->byUser->name}{else}System{/if}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        <h3 class="mt-3">Merge User</h3>
        <p class="alert alert-danger"><b>The account you are currently viewing will be kept. The user ID you enter below
                will be deleted, and its data merged into this user. <i>This action cannot be undone!</i></b></p>
        <form id="merge_user_form"
              action="{path route="TN_Core:User:userProfileEditUserTabMerge" username=$username}">
            <div class="d-flex">
                <a href="{path route="TN_Core:User:listUsers"}" target="_blank"
                   class="btn btn-outline-primary flex-fill">Find&nbsp;User&nbsp;ID&nbsp;To&nbsp;Merge</a>
                <input type="text" id="field_merge_id" class="form-control mx-2 flex-fill"
                       name="secondaryUserId" value="">
                <button type="submit" class="btn btn-outline-primary ms-3 d-flex align-items-center">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                  aria-hidden="true"></span>
                    <i class="bi bi-check-circle me-1 d-none"></i>
                    Merge&nbsp;Users
                </button>
            </div>
        </form>
    {/if}

</div>