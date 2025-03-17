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
            $route = new ($this->stages[$stage - 1])('', '');
            return $route->getPrimaryPath();
        }
        return $this->stages[$stage - 1] ?? 'Unknown Route';
    }
}