<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Route\Access\Restrictions\Anyone;
use TN\TN_Core\Attribute\Route\Component;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Controller\Controller;

class Image extends Controller {
    #[Path('staff/upload-image')]
    #[Anyone]
    #[Component(\TN\TN_Core\Component\Image\UploadImage::class)]
    public function uploadImage(): void {}
}