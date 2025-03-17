<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Command\CLI;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Route\Component;

class Schema extends Controller
{
    #[CommandName('schema/all')]
    #[Component(\TN\TN_Core\CLI\Schema\All::class)]
    public function all(): void {}

    #[CommandName('schema')]
    #[Component(\TN\TN_Core\CLI\Schema\Single::class)]
    public function single(): void {}
}