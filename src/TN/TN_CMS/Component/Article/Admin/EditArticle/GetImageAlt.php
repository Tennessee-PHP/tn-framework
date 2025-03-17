<?php

namespace TN\TN_CMS\Component\Article\Admin\EditArticle;

use TN\TN_CMS\Model\Media\Image;
use TN\TN_Core\Component\Renderer\Text\Text;

class GetImageAlt extends Text
{
    public function prepare(): void
    {
        $img = Image::getFromSrc($_GET['src'] ?? '');
        $this->text = $img->getAlt();
    }
}