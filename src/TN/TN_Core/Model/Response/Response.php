<?php

namespace TN\TN_Core\Model\Response;

use TN\TN_Core\Component\Renderer\Renderer;

class Response
{
    /** @var int */
    public int $code = 200;
    /** @var Renderer */
    public Renderer $renderer;

    /**
     * @param Renderer $renderer
     * @param int $code
     */
    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @return void
     */
    public function respond(): void
    {
        echo $this->renderer->render();
    }
}