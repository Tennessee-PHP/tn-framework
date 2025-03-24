<?php

namespace TN\TN_Billing\Component\GiftSubscription\ActivateGiftSubscription;

use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Core\Attribute\Components\FromRequest;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\User\LoginForm\LoginForm;
use TN\TN_Core\Component\User\RegisterForm\RegisterForm;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

#[Page('Activate Gift Subscription', '', false)]
#[Route('TN_Billing:GiftSubscription:activate')]
class ActivateGiftSubscription extends HTMLComponent
{
    public string $key;
    public ?GiftSubscription $giftSubscription;
    public User $user;
    #[FromRequest] public bool $activate = false;
    public bool $justRedeemed = false;
    public ?User $claimedByUser = null;
    public ?RegisterForm $registerForm;
    public ?LoginForm $loginForm;

    public function prepare(): void
    {
        $this->giftSubscription = GiftSubscription::readFromKey($this->key);
        if (!$this->giftSubscription) {
            throw new ValidationException('Gift subscription not found');
        }

        $this->user = User::getActive();
        if (!$this->user->loggedIn) {
            $this->registerForm = new RegisterForm([
                'redirect_url' => $_ENV['BASE_URL'] . 'gift/activate/' . $this->key
            ]);
            $this->registerForm->prepare();
            $this->loginForm = new LoginForm();
            $this->loginForm->prepare();
        }

        if ($this->user->loggedIn && $this->activate && !$this->giftSubscription->claimed) {
            $this->giftSubscription->redeem($this->user);
            $this->justRedeemed = true;
        }

        if ($this->giftSubscription->claimed) {
            $this->claimedByUser = User::readFromId($this->giftSubscription->claimedByUserId);
        }
    }
}
