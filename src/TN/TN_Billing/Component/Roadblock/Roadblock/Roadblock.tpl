<div class="{$classAttribute}" id="{$idAttribute}">

    <a id="roadblock"></a>

    {if $roadblocked}
        <div class="roadblock bg-primary text-bg-primary rounded-4 p-4">
            <div class="text-center">
                <span class="minor">Already a subscriber? </span>
                <a href="{$BASE_URL}login" class="btn btn-sm btn-outline-light redirect-login-link"><b>Login</b></a>
            </div>

            <h3 class="text-center display-5">
                {if $roadblockContinueMsg}{$roadblockContinueMsg}{else}Continue reading this content{/if}
                {if $requiredPlan && !$requiredPlan->paid}
                    with a <span class="major">100% free</span> {$requiredPlan->name} subscription.
                {/if}
            </h3>

            {if $registerForm}
                <div class="register-form-container" data-bs-theme="light">
                    {$registerForm->render()}
                </div>
            {else}
                <div class="text-center">
                    Roadblocked!
                </div>
            {/if}
        </div>
    {/if}
</div>