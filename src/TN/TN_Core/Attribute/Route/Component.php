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

    public function getRenderer(array $args = []): ?Renderer
    {
        if (!$this->componentClassName) {
            return null;
        }

        $component = new (Stack::resolveClassName($this->componentClassName))([], $args);
        
        // Get the component's reflection class
        $reflection = new \ReflectionClass($component);
        
        // If component extends HTMLComponent
        if ($reflection->isSubclassOf(HTMLComponent::class)) {
            $request = HTTPRequest::get();
            $isReloadable = !empty($reflection->getAttributes(ReloadableAttribute::class));
            
            // If component is reloadable and request has reload=1, use HTML renderer
            if ($isReloadable && $request->getRequest('reload', false)) {
                return HTML::getInstance(['component' => $component]);
            }
            
            // Otherwise use Page renderer for HTML components
            return Page::getInstance(['component' => $component]);
        }
        
        // For non-HTML components, determine renderer based on component's parent class
        if ($reflection->isSubclassOf(\TN\TN_Core\Component\Renderer\JSON\JSON::class)) {
            return JSON::getInstance(['component' => $component]);
        }
        if ($reflection->isSubclassOf(\TN\TN_Core\Component\Renderer\XML\XML::class)) {
            return XML::getInstance(['component' => $component]);
        }
        if ($reflection->isSubclassOf(\TN\TN_Core\Component\Renderer\CSVDownload\CSVDownload::class)) {
            return CSVDownload::getInstance(['component' => $component]);
        }
        
        // Default to Text renderer
        return Text::getInstance(['component' => $component]);
    }
} 