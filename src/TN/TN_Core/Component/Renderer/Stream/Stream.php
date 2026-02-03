<?php

namespace TN\TN_Core\Component\Renderer\Stream;

use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;

/**
 * A streaming response renderer that sends content in chunks
 * Used for real-time content delivery like chat responses
 */
class Stream extends Renderer
{
    use ReadOnlyProperties;

    /** @var string */
    public static string $contentType = 'text/event-stream';

    /** @var bool */
    protected bool $isStreaming = false;

    /** @var bool */
    protected bool $headersSent = false;

    public function headers(): void
    {
        if ($this->headersSent) {
            return;
        }

        // Ensure CORS is sent with the stream (production can miss controller-set headers for SSE)
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');

        parent::headers();

        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set streaming specific headers
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        header('Connection: keep-alive');

        $this->headersSent = true;
    }

    /**
     * Start streaming content
     */
    public function startStream(): void
    {
        if ($this->isStreaming) {
            return;
        }

        // Ensure headers are sent
        if (!$this->headersSent) {
            $this->headers();
        }

        $this->isStreaming = true;
        // Send initial response to establish connection
        echo "retry: 1000\n\n";
        flush();
    }

    /**
     * Send a chunk of data
     * @param string $data The content chunk to send
     * @param string $event Optional event name for EventSource
     */
    public function sendChunk(string $data, string $event = 'message'): void
    {
        if (!$this->isStreaming) {
            $this->startStream();
        }

        // Format the data as a Server-Sent Event
        if ($event) {
            echo "event: {$event}\n";
        }
        // Split data into lines and send each as a data field
        foreach (explode("\n", $data) as $line) {
            echo "data: {$line}\n";
        }
        echo "\n";
        flush();
    }

    /**
     * End the stream
     */
    public function endStream(): void
    {
        if (!$this->isStreaming) {
            return;
        }
        $this->sendChunk('', 'close');
        $this->isStreaming = false;
    }

    /**
     * Required by Renderer but not used for streaming
     * @return string
     */
    public function render(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public static function error(string $message, int $httpResponseCode = 400): Renderer
    {
        return new Text([
            'httpResponseCode' => $httpResponseCode,
            'text' => $message
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function forbidden(): Renderer
    {
        return static::error('forbidden', 403);
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
