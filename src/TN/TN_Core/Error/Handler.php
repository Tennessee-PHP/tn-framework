<?php

namespace TN\TN_Core\Error;

use TN\TN_Core\Model\User\User;

/**
 * handle errors statically
 */
class Handler
{
    public static function handle($errno, $errstr, $errfile, $errline)
    {
        $terminal = false;
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        $log = false;

        switch ($errno) {
            case E_USER_ERROR:
                $errType = 'USER_ERROR';
                $msg = 'FATAL ERROR: ';
                $terminal = true;
                break;

            case E_PARSE:
                $errType = 'PARSE_ERROR';
                $msg = 'PARSE ERROR: ';
                $terminal = true;
                break;

            case E_COMPILE_ERROR:
                $errType = 'COMPILE_ERROR';
                $msg = 'COMPILE ERROR: ';
                $terminal = true;
                break;

            case E_CORE_ERROR:
                $errType = 'CORE_ERROR';
                $msg = 'CORE ERROR: ';
                $terminal = true;
                break;

            case E_USER_WARNING:
            case E_COMPILE_WARNING:
            case E_CORE_WARNING:
            case E_WARNING:
                $errType = 'WARNING';
                $msg = 'WARNING: ';
                return;
                break;

            case E_USER_NOTICE:
            case E_NOTICE:
                $errType = 'NOTICE';
                $msg = 'NOTICE: ';
                return;
                break;

            case E_STRICT:
                $errType = 'ADVISORY';
                $msg = 'PHP ADVISORY: ';
                break;

            case E_DEPRECATED:
                $errType = 'DEPRECATED';
                $msg = 'DEPRECATED: ';
                break;

            default:
                $errType = 'UNKNOWN';
                $msg = "Unknown error type: ";
                break;
        }

        $msg .= "[$errno] $errstr on line $errline in file $errfile";

        if ($_ENV['ENV'] === 'production') {
            $msg = 'Something went wrong there - we\'ve logged the issue. Please try again later.';
            if ($terminal) {
                $loggedError = self::logError($errfile, $errline, $errType, $errstr);
                $msg .= 'Error code: ' . $loggedError->id;
            }
        }

        /* Don't execute PHP internal error handler */
        return true;

    }

    protected static function logError(string $file, string $line, string $type, string $msg): LoggedError
    {
        $loggedError = LoggedError::create();
        try {
            $user = User::getActive();
            if ($user->loggedIn) {
                $loggedError->userId = $user->id;
                $loggedError->username = $user->username;
            }
        } catch (\Exception $e) {
        }
        $loggedError->path = $_SERVER['REQUEST_URI'] ?? '';
        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $loggedError->path .= '?' . $_SERVER['QUERY_STRING'];
        }
        $loggedError->file = $file;
        $loggedError->line = $line;
        $loggedError->type = $type;
        $loggedError->message = $msg;
        $loggedError->timestamp = time();
        Log::log($loggedError);
        return $loggedError;
    }

    public static function checkErrorAtShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && $error['type'] === 1) {
            if ($_ENV['ENV'] === 'production' && false) {
                $loggedError = self::logError('', '', '', $error['message']);
                $msg = 'Something went wrong there - we\'ve logged the issue. Please try again later. If the issue persists, please contact us via ' . $_ENV['SITE_EMAIL'] . ' citing this error code: ' . $loggedError->id;
            } else {
                $msg = $error['message'];
            }
        }
    }

}
