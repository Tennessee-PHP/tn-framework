<?php

namespace TN\TN_Core\Component;

use TN\TN_Core\Component\Renderer\TemplateRender;
use TN\TN_Core\Component\Component;
use TN\TN_Core\Model\Request\HTTPRequest;

/**
 * a component that is rendered via a template
 */
abstract class TemplateComponent extends Component
{
    use TemplateRender;

    /** @var string  */
    public string $template;

    /** @var int  */
    public int $num;

    /** @var int  */
    public static int $numInstances = 0;

    /**
     * @param array $options
     * @param array $pathArguments
     */
    public function __construct(array $options = [], array $pathArguments = [])
    {
        if (self::$numInstances === 0) {
            $request = HTTPRequest::get();
            self::$numInstances = $request ? (min((int)$request->getRequest('componentIdNum', 0), 10000)) : 0;
        }
        self::$numInstances += 1;
        $options['num'] = self::$numInstances;

        parent::__construct($options, $pathArguments);

        // let's set the template if we didn't already!
        if (!isset($this->template)) {
            $this->setTemplate();
        }
    }

    protected function setTemplate(): void
    {
        $parts = explode('\\', static::class);
        array_shift($parts);
        $this->template = implode('/', $parts) . '.tpl';
    }
}
