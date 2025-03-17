<?php

namespace TN\TN_Core\Component\Renderer;

use Smarty\Exception;
use TN\TN_Core\Component\TemplateEngine;
use TN\TN_Core\Model\Theme\Theme;

/**
 *
 * @property-read string $template define this string on the class using this mixin
 */
trait TemplateRender
{
    /**
     * @throws Exception
     */
    public function render(array $data = []): string
    {
        $engine = TemplateEngine::getInstance();
        $engine->assignData(array_merge($this->getTemplateData(), $data));
        return $engine->fetch($this->template);
    }

    public function getTemplateData(): array
    {
        return array_merge([
            'id' => $this->getHtmlId(),
            'theme' => Theme::getTheme()
        ], get_class_vars(static::class), get_object_vars($this));
    }

    protected function getHtmlId(): string
    {
        return str_replace("\\", "_", get_called_class());
    }
}