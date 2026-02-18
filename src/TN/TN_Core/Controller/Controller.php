<?php

namespace TN\TN_Core\Controller;

use ReflectionAttribute;
use ReflectionException;
use ReflectionMethod;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Command\TimeLimit;
use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Attribute\Route\Matcher;
use TN\TN_Core\Attribute\Route\Path;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Error\Access\AccessCsrfInvalidException;
use TN\TN_Core\Error\Access\AccessForbiddenException;
use TN\TN_Core\Error\Access\AccessLoginRequiredException;
use TN\TN_Core\Error\Access\AccessTwoFactorRequiredException;
use TN\TN_Core\Error\Access\AccessUncontrolledException;
use TN\TN_Core\Error\Access\FullPageRoadblockException;
use TN\TN_Core\Error\Access\UnmatchedException;
use TN\TN_Core\Service\CsrfService;
use TN\TN_Core\Error\LoggedError;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\TNException;
use TN\TN_Core\Model\CommandLog\CommandLog;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\Request\Command;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Request\Request;
use TN\TN_Core\Model\Response\HTTPResponse;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Attribute\Route\Access\FullPageRoadblock;
use TN\TN_Core\Attribute\Route\AllowCredentials;
use TN\TN_Core\Attribute\Route\AllowOrigin;
use TN\TN_Core\Attribute\Route\RouteType;
use TN\TN_Core\Component\Renderer\Page\Page;
use TN\TN_Core\Model\User\User;

/**
 * Base controller class handling routing and request processing in the TN Framework.
 * 
 * Controllers are responsible for:
 * - Route definition and handling through attributes
 * - Request processing and response generation
 * - Access control and authorization
 * - Command handling for CLI operations
 * - Component lifecycle management
 *
 * Key Features:
 * - Attribute-based routing (#[Path], #[Route])
 * - Access control (#[RoleOnly], #[UsersOnly], etc.)
 * - Command scheduling (#[Schedule], #[CommandName])
 * - Component integration via #[Component]
 * - Path parameter extraction
 * - Response type handling
 *
 * Example:
 * ```php
 * class UserController extends Controller {
 *     #[Path('users/:id')]
 *     #[Component(UserProfile::class)]
 *     #[UsersOnly]
 *     public function viewProfile(int $id): void {}
 * }
 * ```
 *
 * Controllers are organized by module and can be overridden through the package stack,
 * allowing higher packages to extend or replace route handling from lower packages.
 *
 * @see \TN\TN_Core\Component\HTMLComponent For component implementation details
 * @see HTTPRequest
 * @see HTTPResponse
 */
abstract class Controller
{
    /** Stored when a route has matched, so shutdown handler can apply CORS for fatals from the route's #[AllowOrigin]. */
    private static ?\ReflectionMethod $currentMatchedMethodForCORS = null;

    public static function setCurrentMatchedMethodForCORS(?\ReflectionMethod $method): void
    {
        self::$currentMatchedMethodForCORS = $method;
    }

    public static function getCurrentMatchedMethodForCORS(): ?\ReflectionMethod
    {
        return self::$currentMatchedMethodForCORS;
    }

    public static function path(string $moduleName, string $controllerName, string $routeName, array $args = []): string
    {
        // Append "Controller" suffix if not already present
        if (!str_ends_with($controllerName, 'Controller')) {
            $controllerName .= 'Controller';
        }

        $controllerClassName = Stack::resolveClassName($moduleName . '\\Controller\\' . $controllerName);
        if (!class_exists($controllerClassName)) {
            return '';
        }
        return (new $controllerClassName())->getRoutePath($routeName, $args);
    }

    /**
     * @throws ReflectionException
     */
    public function getRoutePath(string $routeName, array $args = []): string
    {
        // we need a reflection method for the method of name $routeName on this controller
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod($routeName);

        // now we need to get the Path attribute from the method
        $pathAttributes = $method->getAttributes(Path::class);
        $path = $_ENV['BASE_URL'];
        if (!empty($pathAttributes)) {
            $path .= $pathAttributes[0]->newInstance()->path;
        }

        // now we need to string replace :key of each array index
        foreach ($args as $key => $value) {
            $path = str_replace(':' . $key, $value, $path);
        }

        return $path;
    }

    /**
     * get a crontab for all the commands in this controller
     * @return string[]
     */
    public function getCronTab(): array
    {
        $crons = [];

        $reflection = new \ReflectionClass($this);

        // let's iterate through all the methods on the reflection class
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // let's get the command name attribute
            $commandNameAttributes = $method->getAttributes(CommandName::class);
            $schedule = $method->getAttributes(Schedule::class);
            if (empty($commandNameAttributes) || empty($schedule)) {
                continue;
            }

            // now get the command name from the first attribute returned
            $commandName = $commandNameAttributes[0]->newInstance()->argumentName;
            $schedule = $schedule[0]->newInstance()->schedule;

            $crons[] = $schedule . " php " . $_ENV['TN_PHP_ROOT'] . "run.php " . $commandName . ' --cron';
        }

        return $crons;
    }

    /**
     * @param Command $command
     * @return void
     * @throws ReflectionException
     */
    public function run(Command $command): void
    {
        // instantiate a reflection class for the current controller
        $reflection = new \ReflectionClass($this);

        // let's iterate through all the methods on the reflection class
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->routeMatches($command, $method)) {

                // try to get a time limit attribute from the reflection method
                $timeLimitAttribute = $method->getAttributes(TimeLimit::class);
                if (!empty($timeLimitAttribute)) {
                    $timeLimit = $timeLimitAttribute[0]->newInstance()->timeLimit;
                    set_time_limit($timeLimit);
                }

                // Get the Component attribute to determine if this is a CLI component
                $componentAttribute = $method->getAttributes(\TN\TN_Core\Attribute\Route\Component::class);
                $cliClass = null;
                if (!empty($componentAttribute)) {
                    $componentClassName = $componentAttribute[0]->newInstance()->componentClassName;
                    if (is_subclass_of($componentClassName, \TN\TN_Core\CLI\CLI::class)) {
                        $cliClass = $componentClassName;
                    }
                }

                $commandLog = false;
                if ($command->isCron) {
                    try {
                        $commandLog = CommandLog::getInstance();
                        $commandLog->update([
                            'commandName' => $command->name,
                            'startTs' => Time::getNow()
                        ]);
                    } catch (\PDOException) {
                        echo 'Command Log storage error: please ensure database table is created' . PHP_EOL;
                    }
                }

                // now we can run it
                try {
                    if ($command->isCron) {
                        ob_start();
                    }

                    if ($cliClass) {
                        $cli = new $cliClass();
                        $cli->run();
                    } else {
                        $method->invoke($this);
                    }

                    if ($command->isCron) {
                        $commandLog?->update([
                            'result' => ob_get_clean(),
                            'completed' => true,
                            'success' => true,
                            'endTs' => Time::getNow(),
                            'duration' => Time::getNow() - $commandLog->startTs
                        ]);
                    }
                } catch (\Error | \Exception $e) {

                    if (!($e instanceof TNException)) {
                        $e = new TNException($e->getMessage(), (int)$e->getCode(), $e);
                    }

                    if ($command->isCron) {
                        $commandLog?->update([
                            'result' => $e->getDisplayMessage(),
                            'completed' => true,
                            'success' => false,
                            'endTs' => Time::getNow(),
                            'duration' => Time::getNow() - $commandLog->startTs
                        ]);
                    } else {
                        if ($cliClass) {
                            $cli->red($e->getDisplayMessage());
                        } else {
                            echo $e->getDisplayMessage() . PHP_EOL;
                        }
                    }
                    throw $e;
                }
                break;
            }
        }
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse|null
     */
    public function respond(HTTPRequest $request): ?HTTPResponse
    {
        if ($_ENV['SITE_MAINTENANCE_MODE']) {
            if (isset($_GET['_test'])) {
                $_SESSION['skip_maintenance'] = true;
            }
            if (!isset($_SESSION['skip_maintenance'])) {
                $renderer = Stack::resolveClassName(Page::class)::maintenance();
                return new HTTPResponse($renderer, 503);
            }
        }

        // instantiate a reflection class for the current controller
        $reflection = new \ReflectionClass($this);

        // let's iterate through all the methods on the reflection class
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $matcher = $this->routeMatches($request, $method);

            if (!$matcher) {
                continue;
            }

            self::setCurrentMatchedMethodForCORS($method);

            $rendererClass = $this->getRendererClassFromMethod($method);

            try {
                $this->setAccess($request, $method);
            } catch (AccessForbiddenException $e) {
                self::setCurrentMatchedMethodForCORS(null);
                $renderer = $rendererClass::forbidden();
                $renderer->prepare();
                return new HTTPResponse($renderer, 403, $method);
            } catch (AccessLoginRequiredException $e) {
                self::setCurrentMatchedMethodForCORS(null);
                $renderer = $rendererClass::loginRequired();
                $renderer->prepare();
                return new HTTPResponse($renderer, 401, $method);
            } catch (AccessTwoFactorRequiredException $e) {
                self::setCurrentMatchedMethodForCORS(null);
                $pageClass = Stack::resolveClassName(Page::class) ?: Page::class;
                $renderer = $pageClass::twoFactorRequired();
                $renderer->prepare();
                return new HTTPResponse($renderer, 403, $method);
            } catch (AccessUncontrolledException $e) {
                self::setCurrentMatchedMethodForCORS(null);
                $renderer = $rendererClass::uncontrolled();
                $renderer->prepare();
                return new HTTPResponse($renderer, 403, $method);
            } catch (FullPageRoadblockException $e) {
                self::setCurrentMatchedMethodForCORS(null);
                $renderer = $rendererClass::roadblock();
                $renderer->prepare();
                return new HTTPResponse($renderer, 403, $method);
            } catch (UnmatchedException) {
                self::setCurrentMatchedMethodForCORS(null);
                continue;
            }

            if (CsrfService::isStaffMutation($request, $method)) {
                try {
                    CsrfService::validateCsrfForRequest($request);
                } catch (AccessCsrfInvalidException $e) {
                    self::setCurrentMatchedMethodForCORS(null);
                    $renderer = $rendererClass::forbidden();
                    $renderer->prepare();
                    return new HTTPResponse($renderer, 403, $method);
                }
            }

            try {
                $response = $this->getResponse($request, $method, $matcher);
            } catch (ResourceNotFoundException $e) {
                self::setCurrentMatchedMethodForCORS(null);
                $renderer = $rendererClass::error($e->getMessage(), 404);
                $renderer->prepare();
                return new HTTPResponse($renderer, 404, $method);
            } catch (\TN\TN_Core\Error\Access\AccessException $e) {
                self::setCurrentMatchedMethodForCORS(null);
                $renderer = $rendererClass::error($e->getMessage(), 403);
                $renderer->prepare();
                return new HTTPResponse($renderer, 403, $method);
            }

            if ($request->roadblocked && $method->getAttributes(FullPageRoadblock::class)) {
                self::setCurrentMatchedMethodForCORS(null);
                $renderer = $rendererClass::roadblock();
                $renderer->prepare();
                return new HTTPResponse($renderer, 403, $method);
            }

            self::setCurrentMatchedMethodForCORS(null);
            return $response;
        }

        self::setCurrentMatchedMethodForCORS(null);
        return null;
    }

    /**
     * @param Request $request
     * @param ReflectionMethod $method
     * @return Matcher|null
     */
    protected function routeMatches(Request $request, ReflectionMethod $method): ?Matcher
    {
        foreach ($method->getAttributes(Matcher::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $matcher = $attribute->newInstance();
            if ($matcher->matches($request)) {
                return $matcher;
            }
        }
        return null;
    }

    /**
     * @param HTTPRequest $request
     * @param ReflectionMethod $method
     * @return void
     * @throws AccessForbiddenException
     * @throws AccessLoginRequiredException
     * @throws AccessUncontrolledException
     * @throws UnmatchedException
     */
    protected function setAccess(HTTPRequest $request, ReflectionMethod $method): void
    {
        $restrictions = [];
        foreach ($method->getAttributes(Restriction::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $restrictions[] = $attribute->newInstance();
        }
        $request->setAccess($restrictions);
    }

    private function getResponse(HTTPRequest $request, ReflectionMethod $method, Matcher $matcher): HTTPResponse
    {
        try {
            $args = $this->extractArgs($request, $matcher);
            $argValues = array_values($args);

            $routeTypeAttributes = $method->getAttributes(RouteType::class, \ReflectionAttribute::IS_INSTANCEOF);
            $renderer = null;

            if (!empty($routeTypeAttributes)) {
                $routeType = $routeTypeAttributes[0]->newInstance();
                $renderer = $routeType->getRenderer($args);
            }

            if (!$renderer) {
                $renderer = $method->invoke($this, ...$argValues);
            }
            $renderer->prepare();

            // Set 404 status code for FileNotFound routes
            foreach ($method->getAttributes(\TN\TN_Core\Attribute\Route\FileNotFound::class) as $attr) {
                $renderer->httpResponseCode = 404;
                return new HTTPResponse($renderer, 404, $method);
            }

            return new HTTPResponse($renderer, 200, $method);
        } catch (ResourceNotFoundException $e) {
            throw $e;
        } catch (\TN\TN_Core\Error\Access\AccessException $e) {
            throw $e;
        } catch (\Error | \Exception $e) {
            if (!($e instanceof TNException)) {
                $e = new TNException($e->getMessage(), (int)$e->getCode(), $e);
            }

            try {
                LoggedError::log($e, $request);
            } catch (\Exception) {
                // do nothing
            }

            $this->addCorsHeadersForMethod($method);
            $rendererClass = $this->getRendererClassFromMethod($method);
            $renderer = $rendererClass::error($e->getDisplayMessage());
            $renderer->prepare();
            return new HTTPResponse($renderer, $e->httpResponseCode, $method);
        }
    }

    /**
     * Add CORS headers for routes with AllowOrigin/AllowCredentials attributes.
     * Ensures error responses (401, 403, 404, etc.) include CORS headers so
     * cross-origin clients can read the response.
     */
    private function addCorsHeadersForMethod(ReflectionMethod $method): void
    {
        foreach ($method->getAttributes() as $attribute) {
            $attributeName = $attribute->getName();
            if ($attributeName === AllowOrigin::class) {
                $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
                header("Access-Control-Allow-Origin: $origin");
            } elseif ($attributeName === AllowCredentials::class) {
                header('Access-Control-Allow-Credentials: true');
            }
        }
    }

    /**
     * @param HTTPRequest $request
     * @param Matcher $path
     * @return array
     */
    public function extractArgs(HTTPRequest $request, Matcher $path): array
    {
        if (!($path instanceof Path)) {
            return [];
        }

        // get the names of the args in order
        $args = [];
        $matches = [];
        $pattern = "/:[^\\/]+/i";
        preg_match_all($pattern, $path->path, $matches);
        $argNames = $matches[0];
        foreach ($argNames as &$name) {
            $name = substr($name, 1);
        }
        if (count($argNames) === 0) {
            return [$request];
        }

        // now extract the values of the args
        $matches = [];
        $pattern = '/^\/?' .
            preg_replace("/\\\\:[^\\/\\\\]+/i", '([^\\/]+)', preg_quote($path->path, '/')) .
            '$/i';
        preg_match($pattern, $request->path, $matches);
        if (count($matches) <= 1) {
            return $args;
        }
        array_shift($matches);
        while (count($matches) > 0 && count($argNames) > 0) {
            // now for each variable, let's url-decode it
            $args[array_shift($argNames)] = urldecode(array_shift($matches));
        }

        return $args;
    }

    /**
     * Gets the renderer class from a RouteType attribute on the given method.
     * Defaults to Text renderer if no valid RouteType attribute is found or if any errors occur.
     */
    private function getRendererClassFromMethod(ReflectionMethod $method): string
    {
        try {
            $routeTypeAttributes = $method->getAttributes(RouteType::class, ReflectionAttribute::IS_INSTANCEOF);
            if (empty($routeTypeAttributes)) {
                return Text::class;
            }

            $routeType = $routeTypeAttributes[0]->newInstance();
            if (!method_exists($routeType, 'getRendererClass')) {
                return Text::class;
            }

            return $routeType->getRendererClass();
        } catch (\Throwable) {
            return Text::class;
        }
    }

    /**
     * Check if a user can access a component by finding its controlling route and checking restrictions
     * 
     * @param string $componentClassName Full component class name
     * @param User|null $user User to check (defaults to current active user)
     * @return bool True if user has access, false otherwise
     */
    public static function canUserAccessComponent(string $componentClassName, ?User $user = null): bool
    {
        if (!$user) {
            $user = User::getActive();
        }

        // Use existing Stack method to get all controller classes
        foreach (Stack::getChildClasses(Controller::class) as $controllerClass) {
            if (!class_exists($controllerClass)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($controllerClass);

                // Check all public methods for Component attributes
                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $componentAttributes = $method->getAttributes(\TN\TN_Core\Attribute\Route\Component::class);

                    foreach ($componentAttributes as $attribute) {
                        $componentInstance = $attribute->newInstance();
                        if ($componentInstance->componentClassName === $componentClassName) {
                            // Found the controlling method - check access
                            return self::checkMethodAccess($method, $user);
                        }
                    }
                }
            } catch (\ReflectionException) {
                continue;
            }
        }

        // If no controlling route found, assume no access
        return false;
    }

    /**
     * Check if a user can access a specific method by evaluating its restrictions
     * 
     * @param ReflectionMethod $method Method to check
     * @param User $user User to check
     * @return bool True if user has access, false otherwise
     */
    private static function checkMethodAccess(ReflectionMethod $method, User $user): bool
    {
        try {
            // Get all restriction attributes (same logic as setAccess method)
            $restrictions = [];
            foreach ($method->getAttributes(Restriction::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $restrictions[] = $attribute->newInstance();
            }

            // If no restrictions, assume public access
            if (empty($restrictions)) {
                return true;
            }

            // Check each restriction (mimics HTTPRequest::setAccess logic)
            foreach ($restrictions as $restriction) {
                $access = $restriction->getAccess($user);

                // If any restriction denies access, return false
                if (
                    $access === Restriction::FORBIDDEN ||
                    $access === Restriction::LOGIN_REQUIRED ||
                    $access === Restriction::TWO_FACTOR_REQUIRED ||
                    $access === Restriction::UNMATCHED
                ) {
                    return false;
                }
            }

            return true;
        } catch (\Exception) {
            // If any error occurs during access checking, deny access
            return false;
        }
    }
}
