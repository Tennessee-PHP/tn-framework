<?php

namespace TN\TN_Core\Component\Renderer\Page;

class PageResource
{
    public string $url;
    public int $lastModified = 0;

    public function __construct(
        public string            $fileUrl,
        public ?PageResourceType $type = null,
        public bool              $isRelative = false,
        public bool              $liveReload = false,
        public bool              $cacheBuster = false
    )
    {
        $this->url = $fileUrl;
        if ($isRelative) {
            if ($cacheBuster) {
                $this->lastModified = filemtime($_ENV['TN_WEB_ROOT'] . $this->url);
                $this->url .= '?_cb=' . $this->lastModified;
            }
            $this->url = $_ENV['BASE_URL'] . $this->url;
        }
    }
}