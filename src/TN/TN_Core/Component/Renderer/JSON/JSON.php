<?php

namespace TN\TN_Core\Component\Renderer\JSON;

use TN\TN_Core\Error\RateLimitExceededException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Model\Performance\PerformanceDisplayMetrics;
use TN\TN_Core\Model\Performance\PerformanceLog;

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
        PerformanceLog::endRequest();

        $payload = $this->data;
        if (
            is_array($payload)
            && PerformanceLog::shouldRecord()
        ) {
            $raw = PerformanceLog::getCurrentMetrics();
            if ($raw !== null) {
                // Adding a string key to a list makes json_encode emit a JSON object
                // (numeric keys as properties) instead of a JSON array. Skip inline
                // metrics for list payloads so endpoints like GET api/info stay arrays.
                if (!array_is_list($payload)) {
                    $payload['_performanceMetrics'] = PerformanceDisplayMetrics::fromRaw($raw);
                }
            }
        }

        return json_encode($payload);
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
     * JSON error body with optional {@see ValidationException::getFieldErrors()} map for API clients.
     */
    public static function validationError(ValidationException $e): Renderer
    {
        $payload = [
            'result' => 'error',
            'message' => $e->getMessage(),
        ];
        if ($e->errorCode !== null && $e->errorCode !== '') {
            $payload['code'] = $e->errorCode;
        }
        $fieldErrors = $e->getFieldErrors();
        if ($fieldErrors !== null && $fieldErrors !== []) {
            $payload['fieldErrors'] = $fieldErrors;
        }
        return new JSON([
            'httpResponseCode' => $e->httpResponseCode,
            'data' => $payload,
        ]);
    }

    /**
     * JSON error body for {@see RateLimitExceededException} (429, Retry-After, structured retry metadata).
     */
    public static function rateLimitExceeded(RateLimitExceededException $e): Renderer
    {
        $code = $e->errorCode ?? 'rate_limit_exceeded';
        return new JSON([
            'httpResponseCode' => $e->httpResponseCode,
            'data' => [
                'result' => 'error',
                'message' => $e->getMessage(),
                'error' => $code,
                'retry_after' => $e->retryAfter,
            ],
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

