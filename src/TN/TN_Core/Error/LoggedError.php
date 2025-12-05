<?php

namespace TN\TN_Core\Error;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Request\Request;
use TN\TN_Core\Model\User\User;

/**
 * represents an error that's happened on the website
 */
class LoggedError
{
    /* PROPERTIES */

    /** @var string unique identifier for the logged error */
    public string $id = '';

    /** @var int unix timestamp for when this happened */
    public int $timestamp = 0;

    /** @var int the user's id (if one exists) */
    public int $userId = 0;

    /** @var string the user's username (if one exists) */
    public string $username = '';

    /** @var string the path where the error hit */
    public string $path;

    /** @var string the file the error occurred in */
    public string $file;

    /** @var string the line the error occurred in */
    public string $line;

    /** @var string the error message */
    public string $message;

    /** @var string type of error, e.g. "FATAL ERROR" or "WARNING" */
    public string $type;

    private function __construct() {}

    public static function create(): LoggedError
    {
        $error = new static();
        $error->id = static::generateId();
        return $error;
    }

    protected static function generateId(): string
    {
        try {
            return bin2hex(random_bytes(6));
        } catch (\Throwable $exception) {
            return (string)time();
        }
    }

    protected static function getFilename(): string
    {
        return $_ENV['TN_ROOT'] . 'tmp/error.log';
    }

    /**
     * @return LoggedError[]
     */
    public static function getLog(): array
    {
        $log = [];
        foreach (explode(PHP_EOL, file_get_contents(static::getFilename())) as $line) {
            if ($line) {
                $decoded = json_decode($line);
                if (!is_object($decoded)) {
                    continue;
                }
                $error = new LoggedError();
                foreach ($decoded as $key => $value) {
                    $error->$key = $value;
                }
                $log[] = $error;
            }
        }
        return array_reverse($log);
    }

    public static function log(\Error|\Exception $e, Request $request): void
    {
        // if e has a previous error, use that instead
        if ($e instanceof TNException && $e->getPrevious()) {
            $e = $e->getPrevious();
        }

        $loggedError = static::create();
        $loggedError->timestamp = time();
        $loggedError->userId = User::getActive()?->id ?? 0;
        $loggedError->username = User::getActive()?->username ?? '';
        $loggedError->path = $request instanceof HTTPRequest ? trim($request->path, '/') : '';
        $loggedError->file = $e->getFile();
        $loggedError->line = $e->getLine();
        $loggedError->message = $e->getMessage();

        // add the stack trace to the message
        $loggedError->message .= PHP_EOL . PHP_EOL . 'Stack trace:' . PHP_EOL . $e->getTraceAsString();

        $loggedError->type = get_class($e);

        $log = explode(PHP_EOL, file_get_contents(static::getFilename()));
        $log[] = json_encode($loggedError) . PHP_EOL;
        file_put_contents(static::getFilename(), implode(PHP_EOL, array_slice($log, -1000)));
    }
}
