<div id="{$idAttribute}" class="{$classAttribute}">
    {if $expired}
        <div class="alert alert-danger">
            This password reset link has expired. Please request a new one.
        </div>
    {elseif $success}
        <div class="alert alert-success">
            Your password has been reset. You are now logged in.
        </div>
    {else}
        {if $errorMessage}
            <div class="alert alert-danger">
                {$errorMessage}
            </div>
        {/if}
        <form method="POST" action="{$BASE_URL}reset-password" class="" style="max-width: 400px;" id="password_form">
            <div class="row">
                <div class="form-group d-flex flex-row align-items-end row m-0">
                    <div class="form-group d-flex flex-row align-items-end row m-0">
                        <div class="d-flex flex-column col-12 p-0 mb-3">
                            <p>
                                <label class="w-100" for="field_password">Password
                                    <span class="p-params ml-auto text-muted"> *must be between 6-30 characters
                                    </span>
                                </label>
                                <input type="password" name="password" id="field_password"
                                    class="form-control field-password w-100" required />
                            </p>

                            <div class="invalid-feedback" id="invalid_password">
                            </div>
                        </div>
                    </div>
                    <div class="form-group d-flex flex-row align-items-end row m-0 mb-3">
                        <div class="d-flex flex-column col-12 p-0">
                            <p>
                                <label class="w-100" for="field_confirm_password">Confirm
                                    Password</label>
                                <input type="password" name="passwordRepeat" id="field_passwordRepeat"
                                    class="form-control field-confirm-password w-100" required />
                            </p>
                            <div class="invalid-feedback" id="invalid_passwordRepeat">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="key" value="{$key}" />
                </div>
            </div>
            <div class="form-group">

                <input type="submit" value="Reset Password" class="btn btn-primary me-2" />
            </div>
        </form>
    {/if}
</div>