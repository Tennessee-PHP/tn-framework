<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

use TN\TN_Core\Component\Renderer\Page\Page;

#[\Attribute(\Attribute::TARGET_CLASS)]
abstract class RequiresResource
{
    public function __construct() {}

    abstract public function addResource(Page $page): void;
}
