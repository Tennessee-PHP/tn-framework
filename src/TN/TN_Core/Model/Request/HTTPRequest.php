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

    protected array $query;
    protected array $post;
    protected array $cookie;
    protected array $request;
    protected array $files;
    protected array $server;
    protected array $session;

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
     * query controllers for a respond
     * @return void
     */
    public function respond(): void
    {
        $response = null;

        $filename = $this->path . ($this->ext ? '.' . $this->ext : '');
        if (!empty($this->ext) && file_exists($_ENV['TN_WEB_ROOT'] . $filename)) {
            // for these older files, let's reduce the reporting level or we'll just drown in them
            error_reporting(E_ALL & ~E_NOTICE);
            if ($this->path === 'router.php') {
                http_response_code(500);
                echo 'invalid request';
                exit;
            }
            include($_ENV['TN_WEB_ROOT'] . $filename);
            return;
        }

        foreach (Stack::getChildClasses(Controller::class) as $controllerClassName) {
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
                new Text(['text' => '404 Not Found' ]),
                404
            );
        }

        $response->respond();
    }

    public function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}