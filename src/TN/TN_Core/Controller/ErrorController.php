<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\FileNotFound;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Path;

class ErrorController extends Controller
{
    #[FileNotFound]
    #[Anyone]
    #[Component(\TN\TN_Core\Component\Error\NotFoundError\NotFoundError::class)]
    public function fileNotFound(): void {}

    #[Path('errors/log')]
    #[Path('error/log')]
    #[Component(\TN\TN_Core\Component\Error\ErrorLog\ErrorLog::class)]
    #[RoleOnly('super-user')]
    public function errorLog(): void {}
}