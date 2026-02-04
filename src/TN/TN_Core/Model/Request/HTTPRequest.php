<?php

namespace TN\TN_Core\Model\Request;

use TN\TN_Billing\Model\Subscription\Content\Content;
use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Attribute\Route\Access\Restrictions\ContentOwnersOnly;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Error\Access\AccessForbiddenException;
use TN\TN_Core\Error\Access\AccessLoginRequiredException;
use TN\TN_Core\Error\Access\AccessUncontrolledException;
use TN\TN_Core\Error\Access\UnmatchedException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\Response\HTTPResponse;
use TN\TN_Core\Model\User\User;

/**
 * an HTTP request relayed to the framework by a web server
 */
class HTTPRequest extends Request
{
    /** @var array|string[] */
    protected array $indexExt = [
        'php',
        'html',
        'htm',
        'xml'
    ];

    /** @var string  */
    public string $path;

    /** @var string  */
    public string $method;

    /** @var string|null  */
    public ?string $ext;

    /** @var bool  */
    public bool $roadblocked = false;
    public ?Content $contentRequired = null;

    /** @var bool  */
    public bool $notFound = false;

    // Performance tracking properties
    /** @var float Request start time */
    public float $requestStartTime;

    /** @var array Performance timing data */
    public array $performanceTimings = [];

    /** @var array Performance counters */
    public array $performanceCounters = [];

    /** @var bool Whether this request will render a full page or just a component */
    public bool $isFullPageRender = true;

    protected array $query;
    protected array $post;
    protected array $cookie;
    protected array $request;
    protected array $files;
    protected array $server;
    protected array $session;

    /**
     * @var string|null Test request body for functional testing
     */
    private ?string $testRequestBody = null;

    /**
     * @var HTTPRequest|null static instance
     */
    protected static ?Request $instance = null;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        // Initialize performance tracking
        $this->requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $this->performanceTimings = [];
        $this->performanceCounters = [
            'controllers_checked' => 0,
            'components_loaded' => 0,
            'db_queries' => 0,
            'cache_operations' => 0
        ];

        $this->recordTiming('request_start', 'Request received by framework');

        $options = array_merge([
            'query' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
            'request' => $_REQUEST,
            'files' => $_FILES,
            'server' => $_SERVER,
            'session' => $_SESSION
        ], $options);

        parent::__construct($options);

        // Set this as the active instance for global access
        self::$instance = $this;

        $this->recordTiming('request_initialized', 'Request object initialized');
    }
    /**
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null, bool $sanitize = true): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function getPost(string $key, mixed $default = null, bool $sanitize = true): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function getRequestBody(): string
    {
        // Use test request body if available (for functional testing)
        if ($this->testRequestBody !== null) {
            return $this->testRequestBody;
        }

        return file_get_contents('php://input');
    }

    public function getJSONRequestBody(): ?array
    {
        $body = json_decode($this->getRequestBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $body;
    }

    /**
     * Set test request body for functional testing
     * 
     * @param string $body Request body content
     * @return void
     */
    public function setTestRequestBody(string $body): void
    {
        $this->testRequestBody = $body;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null, bool $sanitize = true): mixed
    {
        return $this->cookie[$key] ?? $default;
    }

    public function setCookie(string $key, string $value, array $options = []): void
    {
        $this->cookie[$key] = $value;
        setcookie($key, $value, $options);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function getRequest(string $key, mixed $default = null, bool $sanitize = true): mixed
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function getServer(string $key, mixed $default = null, bool $sanitize = true): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function getSession(string $key, mixed $default = null, bool $sanitize = true): mixed
    {
        return $this->session[$key] ?? $default;
    }

    public function setSession(string $key, mixed $value): void
    {
        if ($value === null) {
            unset($this->session[$key]);
            unset($_SESSION[$key]);
            return;
        }
        $this->session[$key] = $value;
        $_SESSION[$key] = $value;
    }

    /**
     * @param string $key
     * @return array|null
     */
    public function getFiles(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * @param Restriction|Restriction[] $restrictions
     * @return void
     */
    public function setAccess(Restriction|array $restrictions): void
    {
        if (!is_array($restrictions)) {
            $restrictions = [$restrictions];
        }

        if (empty($restrictions)) {
            throw new AccessUncontrolledException();
        }

        $user = User::getActive();
        $this->roadblocked = false;
        $highestContentLevel = 0;
        foreach ($restrictions as $restriction) {
            $access = $restriction->getAccess($user);
            switch ($access) {
                case Restriction::ROADBLOCKED:
                    $this->roadblocked = true;
                    if ($restriction instanceof ContentOwnersOnly) {
                        $content = Content::getInstanceByKey($restriction->content);
                        if ($content instanceof Content && $content->level > $highestContentLevel) {
                            $highestContentLevel = $content->level;
                            $this->contentRequired = $content;
                        }
                    }
                    break;
                case Restriction::FORBIDDEN:
                    throw new AccessForbiddenException();
                case Restriction::LOGIN_REQUIRED:
                    throw new AccessLoginRequiredException();
                case Restriction::UNMATCHED:
                    throw new UnmatchedException();
                default:
                    break;
            }
        }
    }

    /**
     * query controllers for a response
     * @return void
     */
    public function respond(): void
    {
        $this->recordTiming('respond_start', 'Starting response processing');
        // Start performance logging for super-users
        \TN\TN_Core\Model\Performance\PerformanceLog::startRequest();

        $response = null;

        // todo: refactor options handling (CORS preflight; whitelist only)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->recordTiming('options_request', 'Handling OPTIONS request');
            $allowedOrigin = \TN\TN_Core\Model\CORS\CORS::getAllowedOrigin();
            if ($allowedOrigin !== null) {
                header("Access-Control-Allow-Origin: $allowedOrigin");
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
            http_response_code(204);
            exit;
        }

        // Set CORS at the very start so streaming and any early output still get CORS headers
        $allowedOrigin = \TN\TN_Core\Model\CORS\CORS::getAllowedOrigin();
        if ($allowedOrigin !== null) {
            header("Access-Control-Allow-Origin: $allowedOrigin");
            header('Access-Control-Allow-Credentials: true');
        }

        $this->recordTiming('file_check_start', 'Checking for static file');
        $filename = $this->path . ($this->ext ? '.' . $this->ext : '');
        if (!empty($this->ext) && file_exists($_ENV['TN_WEB_ROOT'] . $filename)) {
            $this->recordTiming('static_file_found', 'Static file found, including: ' . $filename);
            // for these older files, let's reduce the reporting level or we'll just drown in them
            error_reporting(E_ALL & ~E_NOTICE);
            include($_ENV['TN_WEB_ROOT'] . $filename);
            return;
        }

        $this->recordTiming('controller_search_start', 'Starting controller search');
        $controllerClasses = Stack::getChildClasses(Controller::class);
        $this->recordTiming('controller_classes_loaded', 'Found ' . count($controllerClasses) . ' controller classes');

        foreach ($controllerClasses as $controllerClassName) {
            $this->incrementCounter('controllers_checked');
            $controller = new $controllerClassName;
            if ($response = $controller->respond($this)) {
                $this->recordTiming('controller_matched', 'Controller matched: ' . $controllerClassName);
                break;
            }
        }

        if (!$this->notFound && !$response) {
            $this->recordTiming('no_match_retry', 'No controller matched, retrying as 404');
            $this->notFound = true;
            $this->respond();
            return;
        }

        if (!$response) {
            $this->recordTiming('404_response', 'Creating 404 response');
            $response = new HTTPResponse(
                new Text(['text' => '404 Not Found']),
                404
            );
        }

        $this->recordTiming('response_send_start', 'Starting response output');
        $response->respond();
        $this->recordTiming('response_complete', 'Response sent to client');
    }

    public function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Record a timing event for performance analysis
     * @param string $event Event name
     * @param string $description Event description
     * @return void
     */
    public function recordTiming(string $event, string $description = ''): void
    {
        $currentTime = microtime(true);
        $this->performanceTimings[] = [
            'event' => $event,
            'description' => $description,
            'timestamp' => $currentTime,
            'elapsed_from_start' => $currentTime - $this->requestStartTime,
            'elapsed_from_previous' => empty($this->performanceTimings)
                ? 0
                : $currentTime - end($this->performanceTimings)['timestamp']
        ];
    }

    /**
     * Increment a performance counter
     * @param string $counter Counter name
     * @param int $increment Amount to increment by
     * @return void
     */
    public function incrementCounter(string $counter, int $increment = 1): void
    {
        if (!isset($this->performanceCounters[$counter])) {
            $this->performanceCounters[$counter] = 0;
        }
        $this->performanceCounters[$counter] += $increment;
    }

    /**
     * Get all performance data
     * @return array
     */
    public function getPerformanceData(): array
    {
        $totalTime = microtime(true) - $this->requestStartTime;

        return [
            'request_start_time' => $this->requestStartTime,
            'total_time' => $totalTime,
            'timings' => $this->performanceTimings,
            'counters' => $this->performanceCounters,
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'current_formatted' => $this->formatBytes(memory_get_usage(true)),
                'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true))
            ]
        ];
    }

    /**
     * Format bytes into human readable format
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get the active HTTPRequest instance for performance tracking
     * @return HTTPRequest|null
     */
    public static function getActiveInstance(): ?HTTPRequest
    {
        return self::$instance instanceof HTTPRequest ? self::$instance : null;
    }
}
