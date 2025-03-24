<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

use TN\TN_Core\Component\Renderer\Page\Page;
use TN\TN_Core\Component\Renderer\Page\PageResource;
use TN\TN_Core\Component\Renderer\Page\PageResourceType;

#[\Attribute(\Attribute::TARGET_CLASS)]
class RequiresTinyMCE extends RequiresResource
{
    public function __construct() {}

    public function addResource(Page $page): void
    {
        $cssResource = new PageResource(
            fileUrl: 'css/index.css',
            type: PageResourceType::CSS,
            isRelative: true,
            liveReload: false,
            cacheBuster: true
        );
        $page->addJsVar('CSS_URL', $cssResource->url);
        $page->addJsVar('TINYMCE_BOOTSTRAP_KEY', $_ENV['TINYMCE_BOOTSTRAP_KEY']);
        $page->addJsUrl('https://cdn.tiny.cloud/1/' . $_ENV['TINYMCE_KEY'] . '/tinymce/6/tinymce.min.js');
    }
}
