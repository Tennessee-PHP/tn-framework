<?php

namespace TN\TN_Core\Attribute\Components\HTMLComponent;

use TN\TN_Core\Component\Title\BreadcrumbEntry;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Breadcrumb
{
    public function __construct(
        public string $textOrClass,
        public string $path = ''
    )
    {
    }

    public function getBreadcrumbEntry(): BreadcrumbEntry
    {
        if (class_exists($this->textOrClass)) {
            return new BreadcrumbEntry([
                'componentClassName' => $this->textOrClass
            ]);
        }
        return new BreadcrumbEntry([
            'text' => $this->textOrClass,
            'path' => $this->path
        ]);
    }
}