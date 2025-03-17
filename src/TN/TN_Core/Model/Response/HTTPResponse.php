<?php

namespace TN\TN_Core\Model\Response;

use TN\TN_Core\Component\Renderer\Renderer;

class HTTPResponse extends Response
{
    /** @var int */
    public int $code = 200;
    /** @var Renderer */
    public Renderer $renderer;

    /**
     * @param Renderer $renderer
     * @param int $code
     */
    public function __construct(Renderer $renderer, int $code = 200)
    {
        parent::__construct($renderer);
        $this->code = $code;
    }

    /**
     * @return void
     */
    public function respond(): void
    {
        http_response_code($this->code);
        $this->renderer->headers();
        parent::respond();
    }
}