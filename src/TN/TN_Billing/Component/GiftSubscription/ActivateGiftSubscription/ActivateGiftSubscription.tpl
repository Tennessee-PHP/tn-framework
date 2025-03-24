<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">

    {if !$user->loggedIn}
        <h3>Sign in or create an account to redeem your gift subscription</h3>
        <div class="d-flex justify-content-center">
            <ul class="nav nav-pills justify-content-center mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#create-account-tab" type="button"
                        role="tab">Create Account</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#login-tab" type="button"
                        role="tab">Login</button>
                </li>
            </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="create-account-tab" role="tabpanel">
                    {$registerForm->render()}
                </div>
                <div class="tab-pane fade" id="login-tab" role="tabpanel">
                    {$loginForm->render()}
                </div>
            </div>
        </div>
    {elseif !$giftSubscription->claimed}
        <h3>Redeem gift for {$giftSubscription->recipientEmail}{if $giftSubscription->gifterEmail} (from {$giftSubscription->gifterEmail}){/if}</h3>
        <div class="d-flex justify-content-center">
            <div class="card">
            
                <h2 class="card-header">{$giftSubscription->getPlan()->name} Plan</h2>
                <div class="card-body">
                    <p>{$giftSubscription->getPlan()->description}</p>

                <p>Redeem this plan to get access!</p>
                <div class="d-flex justify-content-center mt-3">
                    <form action="{$BASE_URL}gift/activate/{$giftSubscription->key}" id="gift_redeem_form" method="POST">

                        <input type="hidden" value="{$giftSubscription->key}" name="key">
                        <input type="hidden" value="true" name="activate">
                        <input type="submit" class="redeem-btn btn btn-cta" value="Redeem This Plan">

                    </form>
                </div>
            </div>
        </div>
    {elseif !$justRedeemed}
        <div class="d-flex justify-content-center">
            <div class="card">
                <h2 class="card-header">{$giftSubscription->getPlan()->name}</h2>
                <div class="card-body">
                    <p>This plan was activated by {$claimedByUser->username}
                        on {$giftSubscription->createdTs|date_format:'%B %d, %Y'}!</p>
                </div>
            </div>
        </div>
    {else}
        <div class="d-flex justify-content-center">
            <div class="card">
                <h2 class="card-header">{$giftSubscription->getPlan()->name}</h2>
                <div class="card-body">
                    <div class="alert-success  p-2">
                        <p>You successfully redeemed this gift subscription onto the account {$claimedByUser->username}.
                        Welcome to your Footballguys premium plan! <a href="{$BASE_URL}">Click here</a> to go to our
                        subscriber
                        homepage to start reading our content and using our tools.</p>
                    </div>
                </div>
            </div>
        </div>
    {/if}

</div>