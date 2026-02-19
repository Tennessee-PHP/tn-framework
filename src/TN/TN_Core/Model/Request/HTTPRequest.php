<?php

namespace TN\TN_Core\Model\Request;

use TN\TN_Billing\Model\Subscription\Content\Content;
use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Attribute\Route\Access\Restrictions\ContentOwnersOnly;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Error\Access\AccessForbiddenException;
use TN\TN_Core\Error\Access\AccessLoginRequiredException;
use TN\TN_Core\Error\Access\AccessTwoFactorRequiredException;
use TN\TN_Core\Error\Access\AccessUncontrolledException;
use TN\TN_Core\Error\Access\UnmatchedException;
use TN\TN_Core\Error\RateLimitExceededException;
use TN\TN_Core\Model\CORS;
use TN\TN_Core\Service\RateLimitService;
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
     * @var string|null Source of auth token when getAuthToken() returned non-null: 'body', 'query', 'cookie', 'header'
     */
    private ?string $authTokenSource = null;

    /**
     * @var HTTPRequest|null static instance
     */
    protected static ?Request $instance = null;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
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

    /**
     * Return the auth token from this request (body, query, Bearer header, then cookie).
     * Header is preferred over cookie when both are present so SPAs sending Bearer are not forced into CSRF.
     * Sets internal authTokenSource for getAuthTokenSource().
     * @return string|null
     */
    public function getAuthToken(): ?string
    {
        $this->authTokenSource = null;
        $jsonRequestBody = $this->getJSONRequestBody();
        if ($jsonRequestBody && isset($jsonRequestBody['access_token']) && $jsonRequestBody['access_token'] !== '') {
            $this->authTokenSource = 'body';
            return $jsonRequestBody['access_token'];
        }
        if ($this->getQuery('access_token') !== null && $this->getQuery('access_token') !== '') {
            $this->authTokenSource = 'query';
            return $this->getQuery('access_token');
        }
        $authHeader = $this->getServer('HTTP_AUTHORIZATION') ?? $this->getServer('REDIRECT_HTTP_AUTHORIZATION') ?? '';
        if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $authHeader, $m)) {
            $this->authTokenSource = 'header';
            return trim($m[1]);
        }
        if ($this->getCookie('TN_token') !== null && $this->getCookie('TN_token') !== '') {
            $this->authTokenSource = 'cookie';
            return $this->getCookie('TN_token');
        }
        return null;
    }

    /**
     * Source of the token returned by getAuthToken(): 'body', 'query', 'cookie', or 'header'. Null if getAuthToken() not yet called or returned null.
     * @return string|null
     */
    public function getAuthTokenSource(): ?string
    {
        return $this->authTokenSource;
    }

    /**
     * Client IP for this request: first IP in X-Forwarded-For if present, otherwise REMOTE_ADDR.
     * @return string
     */
    public function getClientIp(): string
    {
        $forwarded = $this->getServer('HTTP_X_FORWARDED_FOR');
        if ($forwarded !== null && $forwarded !== '') {
            $first = trim(explode(',', (string) $forwarded)[0]);
            if ($first !== '') {
                return $first;
            }
        }
        return (string) $this->getServer('REMOTE_ADDR', 'unknown');
    }

    /**
     * Return the CSRF token from this request: X-CSRF-Token header, then JSON body (csrfToken or _csrf), then POST field.
     * @return string|null
     */
    public function getCsrfToken(): ?string
    {
        $header = $this->getServer('HTTP_X_CSRF_TOKEN');
        if ($header !== null && $header !== '') {
            return is_string($header) ? trim($header) : (string)$header;
        }
        $json = $this->getJSONRequestBody();
        if ($json !== null) {
            $v = $json['csrfToken'] ?? $json['_csrf'] ?? null;
            if ($v !== null && $v !== '') {
                return is_string($v) ? trim($v) : (string)$v;
            }
        }
        $post = $this->getPost('csrfToken') ?? $this->getPost('_csrf');
        if ($post !== null && $post !== '') {
            return is_string($post) ? trim($post) : (string)$post;
        }
        return null;
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
                case Restriction::TWO_FACTOR_REQUIRED:
                    throw new AccessTwoFactorRequiredException();
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
        // Start performance logging for super-users
        \TN\TN_Core\Model\Performance\PerformanceLog::startRequest();

        $response = null;

        if ($this->method === 'OPTIONS') {
            CORS::applyReflectedOriginHeaders();
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-Token');
            header('Access-Control-Max-Age: 86400');
            http_response_code(204);
            return;
        }

        $response = null;

        $filename = $this->path . ($this->ext ? '.' . $this->ext : '');
        if (!empty($this->ext) && file_exists($_ENV['TN_WEB_ROOT'] . $filename)) {
            // for these older files, let's reduce the reporting level or we'll just drown in them
            error_reporting(E_ALL & ~E_NOTICE);
            include($_ENV['TN_WEB_ROOT'] . $filename);
            return;
        }

        try {
            RateLimitService::check($this);
        } catch (RateLimitExceededException $e) {
            header('Retry-After: ' . (int) $e->retryAfter);
            $response = new HTTPResponse(
                new \TN\TN_Core\Component\Renderer\JSON\JSON(['error' => 'rate_limit_exceeded', 'retry_after' => $e->retryAfter]),
                429
            );
            $response->respond();
            return;
        }

        $controllerClasses = Stack::getChildClasses(Controller::class);

        try {
            foreach ($controllerClasses as $controllerClassName) {
                $controller = new $controllerClassName;
                if ($response = $controller->respond($this)) {
                    break;
                }
            }

            if (!$this->notFound && !$response) {
                $this->notFound = true;
                $this->respond();
                return;
            }

            if (!$response) {
                $response = new HTTPResponse(
                    new Text(['text' => '404 Not Found']),
                    404
                );
            }

            $response->respond();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
