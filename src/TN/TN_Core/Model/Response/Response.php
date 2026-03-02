<?php

namespace TN\TN_Core\Model\Response;

use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\JSON\JSON;

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
        $body = $this->renderer->render();

        if ($this->renderer instanceof JSON) {
            $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
            if (str_contains($acceptEncoding, 'gzip')) {
                header('Content-Encoding: gzip');
                $body = gzencode($body, -1, FORCE_GZIP);
            }
        }

        echo $body;
    }
}