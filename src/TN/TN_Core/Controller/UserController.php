<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\AnonymousOnly;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Access\Restrictions\UsersOnly;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Component\Renderer\HTML\Redirect;
use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Model\User\User as UserModel;

class UserController extends Controller
{
    #[Path('log-in')]
    #[Path('sign-in')]
    #[AnonymousOnly]
    #[Component(\TN\TN_Core\Component\User\LoginForm\LoginForm::class)]
    public function login(): void {}

    #[Path('log-out')]
    #[UsersOnly]
    public function logout(): Renderer
    {
        UserModel::getActive()->logout();
        return Redirect::getInstance(['url' => $_ENV['BASE_URL']]);
    }

    #[Path('staff/users/user/:userId/login-as-user')]
    #[RoleOnly('super-user')]
    #[Component(\TN\TN_Core\Component\User\LoginAsUser\LoginAsUser::class)]
    public function loginAsUser(): void {}

    #[Path('users/list')]
    #[RoleOnly('super-user')]
    #[Component(\TN\TN_Core\Component\User\ListUsers\ListUsers::class)]
    public function listUsers(): void {}

    #[Path('users/list/table')]
    #[RoleOnly('super-user')]
    #[Component(\TN\TN_Core\Component\User\ListUsers\ListUsersTable\ListUsersTable::class)]
    public function listUsersTable(): void {}

    #[Path('register')]
    #[Component(\TN\TN_Core\Component\User\RegisterForm\RegisterForm::class)]
    #[AnonymousOnly]
    public function registerForm(): void {}

    #[Path('register/suggest-username')]
    #[AnonymousOnly]
    #[Component(\TN\TN_Core\Component\User\RegisterForm\SuggestUsername::class)]
    public function suggestUsername(): void {}

    #[Path('reset-password')]
    #[Anyone]
    #[Component(\TN\TN_Core\Component\User\ResetPasswordForm\ResetPasswordForm::class)]
    public function resetPassword(): void {}

    #[Path('users/search')]
    #[Anyone]
    #[Component(\TN\TN_Core\Component\User\Search::class)]
    public function search(): void {}
}
