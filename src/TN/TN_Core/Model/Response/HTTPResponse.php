<?php

namespace TN\TN_Core\Model\Response;

use TN\TN_Core\Attribute\Route\AllowOrigin;
use TN\TN_Core\Attribute\Route\ReflectOrigin;
use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Model\CORS;

class HTTPResponse extends Response
{
    /** @var int */
    public int $code = 200;
    /** @var Renderer */
    public Renderer $renderer;
    /** @var \ReflectionMethod|null */
    public ?\ReflectionMethod $matchedMethod = null;

    /**
     * @param Renderer $renderer
     * @param int $code
     * @param \ReflectionMethod|null $matchedMethod
     */
    public function __construct(Renderer $renderer, int $code = 200, ?\ReflectionMethod $matchedMethod = null)
    {
        parent::__construct($renderer);
        $this->code = $code;
        $this->matchedMethod = $matchedMethod;
    }

    /**
     * @return void
     */
    public function respond(): void
    {
        http_response_code($this->code);

        $corsApply = null;
        if ($this->matchedMethod) {
            foreach ($this->matchedMethod->getAttributes() as $attribute) {
                $name = $attribute->getName();
                if ($name === ReflectOrigin::class) {
                    $corsApply = 'reflect';
                    break;
                }
                if ($name === AllowOrigin::class) {
                    $corsApply = 'allowlist';
                    break;
                }
            }
            if ($corsApply === 'reflect') {
                CORS::applyReflectedOriginHeaders();
            } elseif ($corsApply === 'allowlist') {
                CORS::applyCorsHeaders();
            }
        }

        $this->renderer->headers();
        parent::respond();
    }
}
