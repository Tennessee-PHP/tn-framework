<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

use TN\TN_Core\Component\Renderer\Page\Page;

#[\Attribute(\Attribute::TARGET_CLASS)]
class RequiresChartJS extends RequiresResource
{
    public function __construct() {}

    public function addResource(Page $page): void
    {
        $page->addJsUrl('https://cdn.jsdelivr.net/npm/chart.js');
    }
}
