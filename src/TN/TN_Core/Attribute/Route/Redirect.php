<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\HTML\Redirect as RedirectRenderer;

/**
 * the path (e.g. address) for a route
 * * @example
 * Let's define a route that matches json/generate/* - the final part of the URL will be populated on the instance
 * JSON\Generate->args['feed'] - so this path variable should equal:
 *
 * <code>
 * 'json/generate/:feed'
 * </code>
 * 
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Redirect extends RouteType
{
    public function __construct(
        public string $url
    )
    {
    }

    public function getRenderer(array $args = []): Renderer
    {
        return RedirectRenderer::getInstance(['url' => $this->url]);
    }

    public function getRendererClass(): string
    {
        return RedirectRenderer::class;
    }
}