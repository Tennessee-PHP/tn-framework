<?php

namespace TN\TN_Core\Service\Portkey;

use Curl\Curl;
use TN\TN_Core\Error\CodeException;
use TN\TN_Core\Error\PortkeyException;

/**
 * Base class for Portkey API requests
 */
abstract class PortkeyRequest extends Curl
{
    public ?object $jsonResponse = null;
    protected string $url = '';
    protected array $request = [];

    public function __construct()
    {
        parent::__construct();

        if (empty($_ENV['PORTKEY_API_KEY'])) {
            throw new CodeException('PORTKEY_API_KEY must be set in environment variables');
        }

        if (empty($_ENV['PORTKEY_API_ENDPOINT'])) {
            throw new CodeException('PORTKEY_API_ENDPOINT must be set in environment variables');
        }

        $this->setDefaultOptions();
        $this->request();
        $this->validateResponse();
        $this->parseResponse();
    }

    protected function setDefaultOptions(): void
    {
        $this->setHeader('Authorization', 'Bearer ' . $_ENV['PORTKEY_API_KEY']);
        $this->setHeader('Content-Type', 'application/json');
        $this->setHeader('Accept', 'application/json');
    }

    protected function validateResponse(): void
    {
        if ($this->curl_error) {
            throw new PortkeyException($this->curl_error_message);
        }

        $this->jsonResponse = json_decode($this->response);
        if (!$this->jsonResponse) {
            throw new PortkeyException('Invalid JSON response from Portkey API');
        }

        if (isset($this->jsonResponse->error)) {
            throw new PortkeyException(
                $this->jsonResponse->error->message ?? 'Unknown error from Portkey API',
                $this->jsonResponse->error->code ?? 0
            );
        }
    }

    abstract protected function request(): void;
    abstract protected function parseResponse(): void;
}
