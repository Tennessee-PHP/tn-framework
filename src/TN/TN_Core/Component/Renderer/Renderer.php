<?php

namespace TN\TN_Core\Component\Renderer;

use TN\TN_Core\Component\Component;
use TN\TN_Core\Model\Package\Stack;

/**
 * Renderer class - for components that render a response to a request
 *
 * E.g. a Page class would render the contents of an HTML page
 *
 * Should be consumed by a route.
 */
abstract class Renderer extends Component
{
    /** @var string */
    public static string $contentType = 'text/plain';
    public int $httpResponseCode = 200;

    /** @return Renderer factory method */
    public static function getInstance(array $options): Renderer
    {
        $className = Stack::resolveClassName(static::class);
        return new $className($options);
    }

    /**
     * all components must have a way to render themselves
     * @return string
     */
    abstract public function render(): string;

    public function headers(): void
    {
        header('Content-Type: ' . static::$contentType);
        http_response_code($this->httpResponseCode);
    }

    public function prepare(): void {}

    /**
     * @param string $message
     * @return Renderer
     */
    public abstract static function error(string $message, int $httpResponseCode = 400): Renderer;

    /**
     * access forbidden
     * @return Renderer
     */
    public abstract static function forbidden(): Renderer;

    /**
     * login is required to the specified route
     * @return Renderer
     */
    public abstract static function loginRequired(): Renderer;

    /**
     * two-factor verification is required to access the route (e.g. staff routes)
     * @return Renderer
     */
    public abstract static function twoFactorRequired(): Renderer;

    /**
     * the matched route did not have any restrictions on it (set to Anyone if needed)
     * @return Renderer
     */
    public abstract static function uncontrolled(): Renderer;

    /**
     * roadblock response for when content requires payment/subscription
     * @return Renderer
     */
    public abstract static function roadblock(): Renderer;
}
