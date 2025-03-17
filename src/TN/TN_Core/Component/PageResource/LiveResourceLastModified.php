<?php

namespace TN\TN_Core\Component\PageResource;

use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Component\Renderer\Text\Text;

class LiveResourceLastModified extends Text
{
    #[FromQuery]
    public string $resourcePath;

    public function prepare(): void {
        if ($_ENV['ENV'] !== 'development') {
            $this->text = 0;
            return;
        }
        $this->text = filemtime($_ENV['TN_WEB_ROOT'] . $this->resourcePath) + 1;
    }

}