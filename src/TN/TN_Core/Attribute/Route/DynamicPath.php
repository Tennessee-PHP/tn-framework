<?php

namespace TN\TN_Core\Attribute\Route;

use TN\TN_Core\Model\Request\Request;
use TN\TN_Core\Error\CodeException;
use TN\TN_Core\Model\Request\HTTPRequest;

/**
 * Route attribute that uses a static method on the component class to determine if a route matches.
 * This is useful for routes that need to check against dynamic data (e.g. database content).
 * 
 * Usage:
 * #[DynamicPath(Welcome::class, 'dynamicMatch')]
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class DynamicPath extends Matcher
{
    /**
     * @param class-string $componentClass The component class containing the matcher method
     * @param string $methodName The name of the static method to call on the component class
     */
    public function __construct(
        private readonly string $componentClass,
        private readonly string $methodName
    ) {}

    /**
     * Matches a request by calling the specified static method on the component class
     */
    public function matches(Request $origin): bool
    {
        if (!$origin instanceof HTTPRequest) {
            return false;
        }

        if (!method_exists($this->componentClass, $this->methodName)) {
            throw new CodeException(
                "Dynamic path matcher method '{$this->methodName}' not found on component class '{$this->componentClass}'"
            );
        }

        return ($this->componentClass)::{$this->methodName}($origin);
    }
}