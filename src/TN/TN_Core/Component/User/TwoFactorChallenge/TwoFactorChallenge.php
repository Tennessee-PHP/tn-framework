<?php

namespace TN\TN_Core\Component\User\TwoFactorChallenge;

use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Component\HTMLComponent;

/**
 * Renders a form for the user to enter their two-factor verification code.
 * Form POSTs to the configured verifyUrl (e.g. /account/two-factor/verify).
 */
#[Page('Two-Factor Verification')]
class TwoFactorChallenge extends HTMLComponent
{
    /** URL to POST the verification code to */
    public string $verifyUrl = '/account/two-factor/verify';

    /** Optional error message to display */
    public ?string $error = null;

    /** If true, user has no TOTP enrolled; show link to setupUrl instead of code form */
    public bool $needsSetup = false;

    /** URL for "set up 2FA" (e.g. /account/two-factor/setup). Used when needsSetup is true. */
    public ?string $setupUrl = null;

    /** Optional success message (e.g. after completing 2FA setup). */
    public ?string $success = null;

    /** URL to redirect to after successful verification (e.g. the page the user was trying to access). */
    public ?string $redirectUrl = null;

    public function prepare(): void
    {
    }
}
