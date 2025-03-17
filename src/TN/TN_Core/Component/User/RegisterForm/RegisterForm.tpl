<form class="{$classAttribute} d-flex justify-content-center" id="{$idAttribute}"
      data-reload-url="{path route=$reloadRoute}" data-redirect-url="{$redirectUrl}"
      data-success="{if $success}1{else}0{/if}"
    {if $cloudflareTurnstile}data-cloudflare-turnstile="on"{/if}>

    <input type="hidden" name="attemptRegistration" value="true"/>
    <div class="form-container">


        <div class="row mb-3">
            <div class="form-group col-xs-4 col-md-6">
                <label for="field_fname" class="form-label">First Name</label>
                <input type="text" class="form-control" name="first" id="field_first" value="{$first}">
            </div>
            <div class="form-group col-xs-4 col-md-6">
                <label for="field_lname" class="form-label">Last Name</label>
                <input type="text" class="form-control" name="last" id="field_last" value="{$last}">
            </div>
        </div>

        <div class="form-group mb-3">
            <label for="field_email" class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" id="field_email" value="{$email}">
        </div>

        <div class="form-group mb-3">
            <label for="field_username" class="form-label">Username</label>
            <input type="text" class="form-control" name="username" id="field_username" value="{$username}">
        </div>

        <div class="form-group mb-3">
            <label class="w-100 form-label" for="field_password">Password
                <span class="p-params ml-auto text-muted"> *must be between 6-30 characters</span>
            </label>
            <input type="password" name="password" id="field_password" class="field-password w-100 form-control"
                   autocomplete="on" value="{$password}"/>
        </div>

        <div class="form-group mb-3">
            <label class="w-100 form-label" for="field_confirm_password">Confirm Password</label>
            <input type="password" name="passwordRepeat" id="field_passwordRepeat" autocomplete="on"
                   value="{$passwordRepeat}"
                   class="field-confirm-password w-100 form-control"/>
        </div>
        <smaller class="text-secondary mb-3">By signing up and providing us with your email address, you're
            agreeing to our <a href="{$BASE_URL}privacypolicy">Privacy Policy</a> and
            <a href="{$BASE_URL}terms">Terms of Use</a> and to receive emails from Tennessee.
        </smaller>

        {if $cloudflareTurnstile}
            <div class="d-flex justify-content-center my-3">
                <div class="cloudflare-turnstile-container"></div>
            </div>
        {/if}

        <div class="form-group d-flex justify-content-center mt-3">

            <button type="submit" class="btn btn-cta btn-lg">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                {if $success}<i class="bi bi-check-circle"></i>{/if}
                Join Now &#128073;
            </button>

        </div>

        {if !empty($errorMessages)}
            <div class="alert alert-danger mt-3">There were some problems...
                <ul>
                    {foreach $errorMessages as $errorMessage}
                        <li>{$errorMessage}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

    </div>

    <div class="modal fade tn-modal" id="userregister_error" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
</form>