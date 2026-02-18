{* Same layout as forbidden/error pages: background photo with card on top *}
<div class="two-factor-challenge-wrapper position-relative py-5" style="min-height: 60vh; background-image: url('{$IMG_BASE_URL}error/2fa.jpg'); background-size: cover; background-position: center;">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.35);" aria-hidden="true"></div>
    <div class="position-relative d-flex justify-content-center align-items-center py-5">
        <div class="card border-0 shadow-lg text-center p-4" style="max-width: 24rem; width: 100%;">
            <div class="card-body">
                {if $needsSetup && $setupUrl}
                    {if $error}
                        <p class="alert alert-warning">{$error}</p>
                    {/if}
                    <p class="mb-3">This area requires two-factor verification, but you haven't set it up yet.</p>
                    <p class="mb-4">Set up your authenticator app first, then return here to enter your code.</p>
                    <a href="{$setupUrl}" class="btn btn-primary">Set Up Two-Factor Authentication</a>
                {else}
                <form class="{$classAttribute}" id="{$idAttribute}" method="post" action="{$verifyUrl}">
                    {if $redirectUrl}<input type="hidden" name="redirect_url" value="{$redirectUrl}">{/if}
                    {if $success}
                        <p class="alert alert-success">{$success}</p>
                    {/if}
                    {if $error}
                        <p class="alert alert-warning">{$error}</p>
                    {/if}
                    <p class="mb-3">This area requires two-factor verification. Enter the 6-digit code from your authenticator app.</p>
                    <div class="mb-3">
                        <label class="form-label">Verification code</label>
                        <div class="d-flex gap-2 justify-content-center two-factor-digits" data-num="{$num}">
                            <input type="text" class="form-control form-control-lg text-center two-factor-digit" inputmode="numeric" autocomplete="one-time-code" maxlength="1" pattern="[0-9]*" placeholder="·" aria-label="Digit 1" data-index="0" style="width: 3rem; min-width: 3rem; padding-left: 0.25rem; padding-right: 0.25rem;">
                            <input type="text" class="form-control form-control-lg text-center two-factor-digit" inputmode="numeric" autocomplete="one-time-code" maxlength="1" pattern="[0-9]*" placeholder="·" aria-label="Digit 2" data-index="1" style="width: 3rem; min-width: 3rem; padding-left: 0.25rem; padding-right: 0.25rem;">
                            <input type="text" class="form-control form-control-lg text-center two-factor-digit" inputmode="numeric" autocomplete="one-time-code" maxlength="1" pattern="[0-9]*" placeholder="·" aria-label="Digit 3" data-index="2" style="width: 3rem; min-width: 3rem; padding-left: 0.25rem; padding-right: 0.25rem;">
                            <input type="text" class="form-control form-control-lg text-center two-factor-digit" inputmode="numeric" autocomplete="one-time-code" maxlength="1" pattern="[0-9]*" placeholder="·" aria-label="Digit 4" data-index="3" style="width: 3rem; min-width: 3rem; padding-left: 0.25rem; padding-right: 0.25rem;">
                            <input type="text" class="form-control form-control-lg text-center two-factor-digit" inputmode="numeric" autocomplete="one-time-code" maxlength="1" pattern="[0-9]*" placeholder="·" aria-label="Digit 5" data-index="4" style="width: 3rem; min-width: 3rem; padding-left: 0.25rem; padding-right: 0.25rem;">
                            <input type="text" class="form-control form-control-lg text-center two-factor-digit" inputmode="numeric" autocomplete="one-time-code" maxlength="1" pattern="[0-9]*" placeholder="·" aria-label="Digit 6" data-index="5" style="width: 3rem; min-width: 3rem; padding-left: 0.25rem; padding-right: 0.25rem;">
                        </div>
                        <input type="hidden" name="code" class="two-factor-code-value" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Verify</button>
                </form>
                {/if}
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var form = document.getElementById('{$idAttribute}');
    if (!form) return;
    var container = form.querySelector('.two-factor-digits');
    var inputs = form.querySelectorAll('.two-factor-digit');
    var hidden = form.querySelector('.two-factor-code-value');

    function syncToHidden() {
        var code = Array.from(inputs).map(function(inp) { return inp.value; }).join('');
        hidden.value = code;
    }

    function moveFocus(index, dir) {
        var next = index + dir;
        if (next >= 0 && next < inputs.length) inputs[next].focus();
    }

    container.addEventListener('paste', function(e) {
        e.preventDefault();
        var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].value = pasted[i] || '';
        }
        syncToHidden();
        if (pasted.length > 0) inputs[Math.min(pasted.length, inputs.length) - 1].focus();
    });

    container.addEventListener('input', function(e) {
        var inp = e.target;
        if (!inp.classList.contains('two-factor-digit')) return;
        inp.value = inp.value.replace(/\D/g, '').slice(0, 1);
        if (inp.value.length === 1) {
            var idx = parseInt(inp.getAttribute('data-index'), 10);
            moveFocus(idx, 1);
        }
        syncToHidden();
    });

    container.addEventListener('keydown', function(e) {
        var inp = e.target;
        if (!inp.classList.contains('two-factor-digit')) return;
        var idx = parseInt(inp.getAttribute('data-index'), 10);
        if (e.key === 'Backspace' && !inp.value && idx > 0) {
            e.preventDefault();
            inputs[idx - 1].focus();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            moveFocus(idx, -1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            moveFocus(idx, 1);
        }
    });

    form.addEventListener('submit', function() {
        syncToHidden();
    });
})();
</script>
