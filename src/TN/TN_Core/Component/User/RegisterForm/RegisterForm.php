<?php

namespace TN\TN_Core\Component\User\RegisterForm;

use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\FromRequest;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use \TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\IP\IP;
use TN\TN_Core\Model\Provider\Cloudflare\Turnstile;
use TN\TN_Core\Model\User\User;

#[Page('Create an Account')]
#[Route('TN_Core:User:registerForm')]
#[Reloadable]
class RegisterForm extends HTMLComponent
{
    #[FromRequest] public string $redirect_url = '';
    public string $redirectUrl = '';
    #[FromPost] public ?string $first = null;
    #[FromPost] public ?string $last = null;
    #[FromPost] public ?string $email = null;
    #[FromPost] public ?string $username = null;
    #[FromPost] public ?string $password = null;
    #[FromPost] public ?string $passwordRepeat = null;
    #[FromPost] public bool $attemptRegistration = false;
    public bool $success = false;
    public array $errorMessages = [];
    public bool $cloudflareTurnstile = false;

    public function prepare(): void
    {
        if ($_ENV['CLOUDFLARE_TURNSTILE_SITE_KEY']) {
            $this->cloudflareTurnstile = true;
        }

        if ($this->attemptRegistration) {
            $this->processRegistrationAttempt();
        }

        if (empty($this->redirectUrl)) {
            $this->redirectUrl = empty($this->redirect_url) ? $_ENV['BASE_URL'] : $this->redirect_url;
        }
    }

    protected function processRegistrationAttempt(): void
    {
        try {
            $this->allowRegistrationAttempt();
        } catch (ValidationException $e) {
            $this->errorMessages[] = $e->getMessage();
            return;
        }

        // Generate username from email if none provided
        if (empty($this->username) && !empty($this->email)) {
            $this->username = User::emailToUniqueUsername($this->email);
        }

        $user = User::getInstance();
        try {
            $user->update([
                'username' => $this->username,
                'email' => $this->email,
                'first' => $this->first ?? '',
                'last' => $this->last ?? '',
                'password' => empty($this->password) ? 'na' : $this->password,
                'passwordRepeat' => $this->passwordRepeat
            ]);
        } catch (ValidationException $e) {
            $this->errorMessages = $e->errors;
            return;
        }
        $ip = IP::getInstance();
        $ip->recordEvent('user_registration');
        $this->success = true;
        $user->login($this->password);
    }

    /**
     * @throws ValidationException
     */
    protected function allowRegistrationAttempt(): void
    {
        if ($this->cloudflareTurnstile) {
            if (!Turnstile::verify($_POST['cloudflareTurnstileToken'] ?? '')) {
                throw new ValidationException('Unfortunately, we were unable to verify this request. Please try again!');
            }
        }

        $ip = IP::getInstance();
        $attemptsAllowed = 3;
        $allowTestIpAttempts = $_GET['allowTestIpAttempts'] ?? false;
        if (!$ip->eventAllowed('user_registration', $attemptsAllowed, 3600) && $allowTestIpAttempts) {
            throw new ValidationException('It looks like a few accounts have already been recently created from this IP address. Please try again later.');
        }
    }
}
