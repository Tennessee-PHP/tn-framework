<?php

namespace TN\TN_Core\Component\Renderer\Text;

use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;

/**
 * A text response, usually used in the absence of any other matching route
 */
class Text extends Renderer
{
    use ReadOnlyProperties;

    public static string $contentType = 'text/plain';

    public string $text = '';

    public function prepare(): void
    {
    }

    public function render(): string
    {
        return $this->text;
    }

    public static function error(string $message, int $httpResponseCode = 400): Text
    {
        return new Text([
            'httpResponseCode' => $httpResponseCode,
            'text' => $message
        ]);
    }

    public static function forbidden(): Renderer
    {
        return new Text([
            'text' => 'Forbidden'
        ]);
    }

    public static function loginRequired(): Renderer
    {
        return new Text([
            'text' => 'Login required'
        ]);
    }

    public static function twoFactorRequired(): Renderer
    {
        return new Text([
            'httpResponseCode' => 403,
            'text' => 'Two-factor verification required'
        ]);
    }

    public static function uncontrolled(): Renderer
    {
        return new Text([
            'text' => 'No control specified for matching route'
        ]);
    }

    public static function roadblock(): Renderer
    {
        return new Text([
            'text' => 'Subscription required to access this content'
        ]);
    }
}