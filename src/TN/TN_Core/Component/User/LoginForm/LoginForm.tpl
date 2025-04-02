<form class="{$classAttribute}" id="{$idAttribute}"
      data-reload-url="{path route=$reloadRoute}"
      data-login-success="{if $success}1{else}0{/if}"
      data-action="{$action}">
    {if $error}
        <p class="alert alert-warning">{$error}</p>
    {/if}

    {if $action eq 'login'}
        <div class="mb-3">
            <label for="login_field_{$num}" class="form-label">Username or Email Address</label>
            <input type="text" class="form-control" id="login_field_{$num}" name="login" value="{$login}"
                   {if $success}disabled{/if}>
        </div>
        <div class="mb-3">
            <label for="password_field_{$num}" class="form-label">Password</label>
            <input type="password" class="form-control" id="password_field_{$num}" name="password"
                   {if $success}disabled{/if}>
        </div>
        <button type="submit" class="btn btn-primary me-2" {if $success}disabled{/if}>
            {if $success}<i class="bi bi-check-circle"></i>{/if}
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            Log In &#128073;
        </button>
        <a class="btn btn-outline-primary change-action" data-action="reset-password">Lost Password</a>
    {elseif $action eq 'reset-password'}
        {if $success}
            <p class="alert alert-success">If the username or email address you entered matches a user in our database,
                then an email with instructions to reset the password was just sent.</p>
        {else}
            <div class="mb-3">
                <label for="login_field_{$num}" class="form-label">Username or Email Address</label>
                <input type="text" class="form-control" id="login_field_{$num}" name="login" value="{$login}"
                       {if $success}disabled{/if}>
            </div>
            <button type="submit" class="btn btn-primary me-2" {if $success}disabled{/if}>
                {if $success}<i class="bi bi-check-circle"></i>{/if}
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                Request Password Reset
            </button>
        {/if}
        <a class="btn btn-outline-primary change-action" data-action="login">Back To Login</a>
    {/if}

    <div class="create-account-wrapper">
        <p class="mt-3">Don't have an account with us yet? What are you waiting for!</p>
        <a class="btn btn-primary create-account-button" data-url="{path route="TN_Core:User:registerForm"}" href="#create-account">Create Account</a>
    </div>
</form>
