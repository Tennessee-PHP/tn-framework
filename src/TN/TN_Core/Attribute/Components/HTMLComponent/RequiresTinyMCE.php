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
        $page->addJsVar('TINYMCE_KEY', $_ENV['TINYMCE_KEY']);

        // Add font URLs
        $page->addJsVar('FONT_URLS', [
            'https://fonts.googleapis.com/css2?family=Anton&family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap'
        ]);

        // Temporary fix for AWS outage - using Cloudflare CDN instead of Tiny Cloud
        // Note: This disables premium features (tinycomments, powerpaste)
        // Restore original line below when AWS is back up
        $page->addJsUrl('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.0.0/tinymce.min.js');
        // Original: $page->addJsUrl('https://cdn.tiny.cloud/1/' . $_ENV['TINYMCE_KEY'] . '/tinymce/6/tinymce.min.js');
    }
}
