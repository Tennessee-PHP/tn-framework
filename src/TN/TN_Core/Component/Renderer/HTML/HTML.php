<?php

namespace TN\TN_Core\Component\Renderer\HTML;

use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\TemplateRender;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Component\TemplateComponent;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Model\User\User;

/**
 * A whole page of the website to be outputted to the browser
 */
class HTML extends Renderer
{
    use ReadOnlyProperties;
    use TemplateRender;

    /** @var string */
    public static string $contentType = 'text/html';

    public User $user;

    /** @var TemplateComponent */
    public TemplateComponent $component;

    public function prepare(): void
    {
        $this->user = User::getActive();
        $this->component->prepare();
    }

    public function render(): string
    {
        return $this->component->render();
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