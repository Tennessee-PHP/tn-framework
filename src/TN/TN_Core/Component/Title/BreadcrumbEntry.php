<?php

namespace TN\TN_Core\Component\Title;

use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Package\Stack;

class BreadcrumbEntry
{
    public string $componentClassName;
    public string $text;
    public ?string $path;

    public function __construct(array $options)
    {
        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
    }

    public function prepare(): void
    {
        $this->setText();
        $this->setPath();
    }

    protected function setText(): void
    {
        if (!empty($this->text)) {
            return;
        }
        if (!empty($this->componentClassName)) {
            $reflection = new \ReflectionClass($this->componentClassName);
            $attributes = $reflection->getAttributes(Page::class);
            if (!empty($attributes)) {
                $this->text = $attributes[0]->newInstance()->title;
                return;
            }
        }
        $this->text = '';
    }

    protected function setPath(): void
    {
        if (!empty($this->path)) {
            return;
        }

        if (!empty($this->componentClassName)) {
            $reflection = new \ReflectionClass($this->componentClassName);
            $attributes = $reflection->getAttributes(Route::class);
            if (!empty($attributes)) {
                $parts = explode(':', $attributes[0]->newInstance()->route);
                $this->path = Controller::path($parts[0], $parts[1], $parts[2]);
                return;
            }
        }
        $this->path = false;
    }
}
