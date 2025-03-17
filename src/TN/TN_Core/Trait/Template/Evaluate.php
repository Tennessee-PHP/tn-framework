<?php

namespace TN\TN_Core\Trait\Template;

use TN\TN_Core\Component\TemplateEngine;

/**
 * evaluate a template
 */
trait Evaluate
{
    /**
     * @param string $template
     * @param array $data
     * @return string
     * @throws \SmartyException
     */
    protected function evaluateTemplate(string $template, array $data = []): string
    {
        $engine = TemplateEngine::getInstance();
        $engine->assignData($data);
        return $engine->fetch($template);
    }
}