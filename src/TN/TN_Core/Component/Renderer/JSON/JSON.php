<?php

namespace TN\TN_Core\Component\Renderer\JSON;

use TN\TN_Core\Component\Renderer\Renderer;

class JSON extends Renderer
{
    /** @var string */
    public static string $contentType = 'application/json';

    public mixed $data = [];

    public function headers(): void
    {
        parent::headers();
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        return json_encode($this->data);
    }

    /**
     * @inheritDoc
     */
    public static function error(string $message, int $httpResponseCode = 400): Renderer
    {
        return new JSON([
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
        $data = [
            'result' => 'error',
            'message' => 'forbidden',
        ];
        if (($_ENV['ENV'] ?? '') === 'development') {
            $reason = \TN\TN_Core\Error\ForbiddenReason::get();
            if ($reason !== null) {
                $data['forbiddenReason'] = $reason;
            }
        }
        return new JSON([
            'httpResponseCode' => 403,
            'data' => $data,
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function loginRequired(): Renderer
    {
        return static::error('login required', 401);
    }

    /**
     * @inheritDoc
     * For AJAX/API: ask client to open a new window to complete 2FA, then retry.
     */
    public static function twoFactorRequired(): Renderer
    {
        return new JSON([
            'httpResponseCode' => 403,
            'data' => [
                'requireTwoFactor' => true,
                'message' => 'This action requires two-factor verification. Please open a new window or tab, sign in there and complete two-factor verification, then return here and try again.'
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function uncontrolled(): Renderer
    {
        return static::error('no control specified for matching route', 500);
    }

    public static function roadblock(): Renderer
    {
        return static::error('subscription required to access this content', 403);
    }
}

