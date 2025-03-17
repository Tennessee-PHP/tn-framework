<?php

namespace TN\TN_S3Download\Controller;

use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;

class S3Download extends Controller
{
    #[Path('download')]
    #[Component(\TN\TN_S3Download\Component\S3Download\Download\Download::class)]
    #[Anyone]
    public function download(): void {}
}