<?php

namespace TN\TN_Reporting\Model\Funnel;

/**
 * a funnel to count progress of users through
 * 
 */
abstract class Funnel
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string non-db identifier */
    public string $key;

    /** @var string the name of the funnel (user-facing) */
    public string $name;

    /** @var array array of route classes */
    public array $stages;

    /**
     * get the stage of a route in this funnel as an 1-based integer or return false
     * @param string $routeCls
     * @return int|false
     */
    public function getRouteStage(string $routeCls): int|false
    {
        $search = array_search($routeCls, $this->stages);
        return is_int($search) ? $search + 1 : false;
    }

    /**
     * gets the route class for a stage of the route
     * @param int $stage
     * @return string
     */
    public function getStageRoute(int $stage): string
    {
        if (isset($this->stages[$stage - 1])) {
            $routeString = $this->stages[$stage - 1];
            $parts = explode(':', $routeString);

            if (count($parts) !== 3) {
                return 'Invalid Route Format';
            }

            $moduleName = $parts[0];
            $controllerName = $parts[1];
            $methodName = $parts[2];

            // Convert route string to controller class name using the same logic as Controller::path()
            if (!str_ends_with($controllerName, 'Controller')) {
                $controllerName .= 'Controller';
            }

            $controllerClassName = \TN\TN_Core\Model\Package\Stack::resolveClassName($moduleName . '\\Controller\\' . $controllerName);

            if (!class_exists($controllerClassName)) {
                return 'Controller Not Found: ' . $controllerClassName;
            }

            $controller = new $controllerClassName();
            return $controller->getRoutePath($methodName);
        }
        return $this->stages[$stage - 1] ?? 'Unknown Route';
    }
}
