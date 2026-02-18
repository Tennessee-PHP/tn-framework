<?php

namespace TN\TN_Core\Component\User\LoginAsUser;

use TN\TN_Core\Component\Renderer\HTML\Redirect;
use TN\TN_Core\Model\User\User as UserModel;

/**
 * Admin: start login-as (impersonate) session for target user and redirect.
 */
class LoginAsUser extends Redirect
{
    public int $userId;

    public function prepare(): void
    {
        $user = UserModel::getActive();
        $user->loginAs($this->userId);
        $this->url = $_POST['redirect_url'] ?? $_ENV['BASE_URL'];
    }
}
