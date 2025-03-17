<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;

class PageResource extends Controller
{
    #[Path('check-live-resource')]
    #[Anyone]
    #[Component(\TN\TN_Core\Component\PageResource\LiveResourceLastModified::class)]
    public function liveResourceLastModified(): void {}
}