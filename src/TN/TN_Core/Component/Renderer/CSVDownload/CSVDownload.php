<?php

namespace TN\TN_Core\Component\Renderer\CSVDownload;

use TN\TN_Core\Component\Renderer\Renderer;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;

/**
 * A text response, usually used in the absence of any other matching route
 */
class CSVDownload extends Renderer
{
    use ReadOnlyProperties;

    public static string $contentType = 'application/octet-stream';

    public string $csv = '';
    public string $filename = '';

    public function prepare(): void
    {
    }

    public function headers(): void
    {
        parent::headers();
        header("Content-Disposition: attachment;filename=" . $this->filename);
    }

    public function render(): string
    {
        return $this->csv;
    }

    public static function error(string $message, int $httpResponseCode = 400): Renderer
    {
        return new Text([
            'httpResponseCode' => $httpResponseCode,
            'text' => $message
        ]);
    }

    public static function forbidden(): Renderer
    {
        return new CSVDownload([
            'text' => 'Forbidden'
        ]);
    }

    public static function loginRequired(): Renderer
    {
        return new CSVDownload([
            'text' => 'Login required'
        ]);
    }

    public static function uncontrolled(): Renderer
    {
        return new CSVDownload([
            'text' => 'No control specified for matching route'
        ]);
    }

    public static function roadblock(): Renderer
    {
        return new Text([
            'text' => 'Subscription required to access this content'
        ]);
    }
}