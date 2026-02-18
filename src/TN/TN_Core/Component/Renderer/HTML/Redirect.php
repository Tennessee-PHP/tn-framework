<?php

namespace TN\TN_Core\Component\Renderer\HTML;

use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;

/**
 * A whole page of the website to be outputted to the browser
 */
class Redirect extends Renderer
{
    use ReadOnlyProperties;

    /** @var string */
    public static string $contentType = 'text/html';

    /** @var string  */
    public string $url;

    public function render(): string
    {
        return '';
    }

    public function headers(): void
    {
        header('Location: ' . $this->url);
    }

    /**
     * @param string $message
     * @param int $httpResponseCode
     * @return Renderer
     */
    public static function error(string $message, int $httpResponseCode = 400): Renderer
    {
        return Text::getInstance([
            'httpResponseCode' => $httpResponseCode,
            'text' => $message
        ]);
    }

    public static function forbidden(): Renderer
    {
        return Text::getInstance([
            'text' => 'Access forbidden'
        ]);
    }

    public static function loginRequired(): Renderer
    {
        return Text::getInstance([
            'text' => 'Login required'
        ]);
    }

    public static function twoFactorRequired(): Renderer
    {
        return Text::getInstance([
            'httpResponseCode' => 403,
            'text' => 'Two-factor verification required'
        ]);
    }

    public static function uncontrolled(): Renderer
    {
        return Text::getInstance([
            'text' => 'No access specified for route'
        ]);
    }

    public static function roadblock(): Renderer
    {
        return Text::getInstance([
            'text' => 'Subscription required to access this content'
        ]);
    }
}