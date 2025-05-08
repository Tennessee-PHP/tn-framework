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
    /** @var callable|null */
    protected $streamCallback = null;
    protected bool $stream = false;
    protected string $streamContent = '';

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

        if ($this->stream) {
            $this->setupStreamHandling();
        }

        $this->request();
        $this->exec();

        if ($this->curl_error) {
            throw new PortkeyException($this->curl_error_message);
        }

        if (!$this->stream) {
            $this->validateResponse();
            $this->parseResponse();
        }
    }

    protected function setDefaultOptions(): void
    {
        $this->setHeader('x-portkey-api-key', $_ENV['PORTKEY_API_KEY']);
        $this->setHeader('x-portkey-virtual-key', $_ENV['PORTKEY_API_VIRTUAL_KEY']);
        $this->setHeader('Content-Type', 'application/json');
        $this->setHeader('Accept', 'application/json');
    }

    protected function validateResponse(): void
    {
        // If response starts with { or [, treat it as JSON
        if (str_starts_with(trim($this->response), '{') || str_starts_with(trim($this->response), '[')) {
            $this->jsonResponse = json_decode($this->response);
            if (!$this->jsonResponse) {
                throw new PortkeyException('Invalid JSON response from Portkey API');
            }

            if (isset($this->jsonResponse->error)) {
                throw new PortkeyException(
                    $this->jsonResponse->error->message ?? 'Unknown error from Portkey API',
                    is_numeric($this->jsonResponse->error->code ?? 0) ? (int)($this->jsonResponse->error->code) : 0
                );
            }
        } else {
            // For non-JSON responses, store the raw text
            $this->jsonResponse = (object)['choices' => [(object)['message' => (object)['content' => $this->response]]]];
        }
    }

    /**
     * Set up streaming response handling
     */
    protected function setupStreamHandling(): void
    {
        // Set up callback for processing chunks
        $this->setOpt(CURLOPT_WRITEFUNCTION, function ($ch, $data) {
            // Each line is a separate JSON object
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                if (str_starts_with($line, 'data: ')) {
                    $jsonData = substr($line, 6); // Remove 'data: ' prefix
                    $chunk = json_decode($jsonData);

                    if (isset($chunk->error)) {
                        throw new PortkeyException(
                            $chunk->error->message ?? 'Unknown error from Portkey API',
                            is_numeric($chunk->error->code ?? 0) ? (int)($chunk->error->code) : 0
                        );
                    }

                    if (isset($chunk->choices[0]->delta->content)) {
                        $content = $chunk->choices[0]->delta->content;
                        if ($this->streamCallback) {
                            ($this->streamCallback)($content);
                        }
                        $this->streamContent .= $content;
                    }

                    // Check if this is the last chunk
                    if (isset($chunk->choices[0]->finish_reason) && $chunk->choices[0]->finish_reason === 'stop') {
                        // Set the final response and validate it
                        $this->response = $this->streamContent;
                        $this->validateResponse();
                        $this->parseResponse();
                    }
                }
            }
            return strlen($data);
        });
    }

    abstract protected function request(): void;
    abstract protected function parseResponse(): void;
}
