<?php

namespace TN\TN_Core\Component\Renderer\XML;

use TN\TN_Core\Component\Renderer\Renderer;
use Spatie\ArrayToXml\ArrayToXml;


class XML extends Renderer
{
    /** @var string */
    public static string $contentType = 'application/xml';

    public array $data = [];

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        return ArrayToXml::convert($this->data);
    }

    /**
     * @param string $message
     * @param int $httpResponseCode
     * @inheritDoc
     */
    public static function error(string $message, int $httpResponseCode = 400): Renderer
    {
        return new XML([
            'httpResponseCode' => $httpResponseCode,
            'data' => [
                'result' => 'error',
                'message' => $message
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function forbidden(): Renderer
    {
        return static::error('forbidden');
    }

    /**
     * @inheritDoc
     */
    public static function loginRequired(): Renderer
    {
        return static::error('login required');
    }

    public static function twoFactorRequired(): Renderer
    {
        return static::error('two-factor verification required', 403);
    }

    /**
     * @inheritDoc
     */
    public static function uncontrolled(): Renderer
    {
        return static::error('no control specified for matching route');
    }

    public static function roadblock(): Renderer
    {
        return static::error('subscription required to access this content', 403);
    }
}