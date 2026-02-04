<?php

namespace TN\TN_Core\Model\Response;

use TN\TN_Core\Attribute\Route\AllowOrigin;
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

        if ($this->matchedMethod) {
            $hasAllowOrigin = false;
            foreach ($this->matchedMethod->getAttributes() as $attribute) {
                if ($attribute->getName() === AllowOrigin::class) {
                    $hasAllowOrigin = true;
                    break;
                }
            }
            if ($hasAllowOrigin) {
                CORS::applyCorsHeaders();
            }
        }

        $this->renderer->prepare();
        $this->renderer->headers();
        parent::respond();
    }
}
