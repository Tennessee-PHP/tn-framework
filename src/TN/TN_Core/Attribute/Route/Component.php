<?php

namespace TN\TN_Core\Attribute\Route;

use FBG\TN_Core\Component\LegacyComponent;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\PageComponent;
use TN\TN_Core\Component\Renderer\CSVDownload\CSVDownload;
use TN\TN_Core\Component\Renderer\SQLDownload\SQLDownload;
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

        try {
            $resolvedClass = Stack::resolveClassName($this->componentClassName);
            if ($resolvedClass === false) {
                return Text::class;
            }
            $reflection = new \ReflectionClass($resolvedClass);

            // If component implements PageComponent
            if ($reflection->implementsInterface(PageComponent::class)) {
                $request = HTTPRequest::get();
                $isReloadable = !empty($reflection->getAttributes(ReloadableAttribute::class));

                // If component is reloadable and request has reload=1, use HTML renderer
                if ($isReloadable && $request->getRequest('reload', false)) {
                    $request->isFullPageRender = false;
                    return HTML::class;
                }

                // Otherwise use Page renderer for HTML components
                $request->isFullPageRender = true;
                return Page::class;
            }

            // For non-HTML components, find the immediate Renderer subclass
            $rendererClass = null;
            $currentClass = $reflection;
            while ($currentClass && !$rendererClass) {
                $parentClass = $currentClass->getParentClass();
                if ($parentClass && $parentClass->getName() === Renderer::class) {
                    $rendererClass = $currentClass->getName();
                }
                $currentClass = $parentClass;
            }

            // Return the found renderer class or default to Text
            return $rendererClass ?: Text::class;
        } catch (\Throwable) {
            return Text::class;
        }
    }

    public function getRenderer(array $args = []): ?Renderer
    {
        if (!$this->componentClassName) {
            return null;
        }

        $component = new (Stack::resolveClassName($this->componentClassName))([], $args);
        if (!($component instanceof HTMLComponent) && !($component instanceof LegacyComponent)) {
            return $component;
        }
        $rendererClass = $this->getRendererClass();

        $renderer = $rendererClass::getInstance(['component' => $component]);
        return $renderer;
    }
}
