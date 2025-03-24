<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Renderer\CSVDownload\CSVDownload;
use TN\TN_Core\Component\Renderer\HTML\HTML;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Component\Renderer\Page\Page;
use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Component\Renderer\XML\XML;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable as ReloadableAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Component extends RouteType
{
    public function __construct(
        public readonly string $componentClassName
    ) {}

    public function getRendererClass(): string
    {
        if (!$this->componentClassName) {
            return Text::class;
        }

        $component = new (Stack::resolveClassName($this->componentClassName))();
        $reflection = new \ReflectionClass($component);

        // If component extends HTMLComponent
        if ($reflection->isSubclassOf(HTMLComponent::class)) {
            $request = HTTPRequest::get();
            $isReloadable = !empty($reflection->getAttributes(ReloadableAttribute::class));

            // If component is reloadable and request has reload=1, use HTML renderer
            if ($isReloadable && $request->getRequest('reload', false)) {
                return HTML::class;
            }

            // Otherwise use Page renderer for HTML components
            return Page::class;
        }

        // For non-HTML components, determine renderer based on component's parent class
        if ($reflection->isSubclassOf(JSON::class)) {
            return JSON::class;
        }
        if ($reflection->isSubclassOf(XML::class)) {
            return XML::class;
        }
        if ($reflection->isSubclassOf(CSVDownload::class)) {
            return CSVDownload::class;
        }

        // Default to Text renderer
        return Text::class;
    }

    public function getRenderer(array $args = []): ?Renderer
    {
        if (!$this->componentClassName) {
            return null;
        }

        $component = new (Stack::resolveClassName($this->componentClassName))([], $args);
        if ($component instanceof JSON) {
            return $component;
        }
        $rendererClass = $this->getRendererClass();

        return $rendererClass::getInstance(['component' => $component]);
    }
}
