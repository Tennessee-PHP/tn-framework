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
        file_put_contents('/var/www/html/.cursor/debug.log', json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H-respond-entry',
            'location' => __FILE__ . ':' . __LINE__,
            'message' => 'HTTPResponse::respond() entered',
            'data' => ['code' => $this->code, 'matchedMethod' => $this->matchedMethod ? get_class($this->matchedMethod->getDeclaringClass()) . '::' . $this->matchedMethod->getName() : null],
            'timestamp' => time() * 1000
        ]) . "\n", FILE_APPEND);
        http_response_code($this->code);

        $hasAllowOrigin = false;
        $matchedMethodClass = null;
        $matchedMethodName = null;
        if ($this->matchedMethod) {
            $matchedMethodClass = $this->matchedMethod->getDeclaringClass()->getName();
            $matchedMethodName = $this->matchedMethod->getName();
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
        file_put_contents('/var/www/html/.cursor/debug.log', json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H-response',
            'location' => __FILE__ . ':' . __LINE__,
            'message' => 'HTTPResponse::respond',
            'data' => [
                'code' => $this->code,
                'matchedMethod' => $matchedMethodClass ? $matchedMethodClass . '::' . $matchedMethodName : null,
                'hasAllowOrigin' => $hasAllowOrigin,
            ],
            'timestamp' => time() * 1000
        ]) . "\n", FILE_APPEND);

        $this->renderer->headers();
        parent::respond();
    }
}
