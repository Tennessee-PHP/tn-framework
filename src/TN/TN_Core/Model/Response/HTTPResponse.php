<?php

namespace TN\TN_Core\Model\Response;

use TN\TN_Core\Attribute\Route\AllowCredentials;
use TN\TN_Core\Attribute\Route\AllowOrigin;
use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Model\CORS\CORS;

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

        // Set CORS before prepare() so streaming responses send CORS before any output (whitelist only)
        if ($this->matchedMethod) {
            $allowedOrigin = CORS::getAllowedOrigin();
            $hasAllowCredentials = false;
            foreach ($this->matchedMethod->getAttributes() as $attribute) {
                $attributeName = $attribute->getName();
                if ($attributeName === AllowOrigin::class && $allowedOrigin !== null) {
                    header("Access-Control-Allow-Origin: $allowedOrigin");
                } elseif ($attributeName === AllowCredentials::class) {
                    $hasAllowCredentials = true;
                }
            }
            if ($hasAllowCredentials && $allowedOrigin !== null) {
                header('Access-Control-Allow-Credentials: true');
            }
        }

        $this->renderer->prepare();
        $this->renderer->headers();
        parent::respond();
    }
}
