<?php

namespace TN\TN_Billing\Component\Roadblock\Roadblock;

use TN\TN_Billing\Model\Subscription\Content\Content;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use \TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Component\User\RegisterForm\RegisterForm;

class Roadblock extends HTMLComponent
{
    public bool $buttonOnly = false;
    public ?bool $roadblocked = null;
    public ?Plan $requiredPlan = null;
    public string $roadblockContinueMsg = 'Continue reading this content';
    public User $user;
    public ?Plan $userPlan = null;
    public ?Content $content = null;
    public ?RegisterForm $registerForm = null;

    public function prepare(): void
    {
        $request = HTTPRequest::get();
        if ($this->roadblocked === null) {
            $this->roadblocked = $request->roadblocked;
        }
        $this->user = User::getActive();
        $this->userPlan = $this->user->getPlan();
        if (!$this->content) {
            $this->content = $request->contentRequired;
        }

        // Only set requiredPlan if it hasn't been set already
        if (!$this->requiredPlan) {
            $this->requiredPlan = Plan::getPlanForLevel($this->content ? $this->content->level : 0);
        }

        // Add RegisterForm component if required plan is free
        if ($this->requiredPlan && !$this->requiredPlan->paid) {
            // Redirect back to the current page after registration
            $request = HTTPRequest::get();
            $redirectUrl = $_ENV['BASE_URL'] . ltrim($request->path, '/');

            // Special handling for chat responses
            if (!empty($_GET['conversationId'])) {
                $redirectUrl = $_ENV['BASE_URL'] . 'assistant/chat/' . intval($_GET['conversationId']);
            }

            $this->registerForm = new RegisterForm([
                'redirectUrl' => $redirectUrl
            ]);
            $this->registerForm->prepare();
        }
    }

    public static function printLegacyRoadblock(string $content): void
    {
        $roadblock = new Roadblock(['content' => Content::getInstanceByKey($content)]);
        $roadblock->prepare();
        echo $roadblock->render();
    }
}
