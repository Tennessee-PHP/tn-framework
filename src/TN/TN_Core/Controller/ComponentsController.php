<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Path;

class ComponentsController extends Controller
{
    #[Path('style-guide')]
    #[RoleOnly('super-user')]
    #[Component(\TN\TN_Core\Component\StyleGuide\StyleGuide::class)]
    public function styleGuide(): void {}

    #[CommandName('components/map')]
    #[Component(\TN\TN_Core\CLI\Components\Map::class)]
    public function map(): void {}
}