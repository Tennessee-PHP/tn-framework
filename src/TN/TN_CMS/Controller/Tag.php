<?php

namespace TN\TN_CMS\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Controller\Controller;

class Tag extends Controller
{
    #[Path('tags/search')]
    #[Anyone]
    #[Component(\TN\TN_CMS\Component\Tag\Search::class)]
    public function search(): void {}
}