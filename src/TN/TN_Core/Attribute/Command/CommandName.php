<?php

namespace TN\TN_Core\Attribute\Command;

use TN\TN_Core\Attribute\Route\Matcher;
use TN\TN_Core\Model\Request\Command;
use TN\TN_Core\Model\Request\Request;

/**
 * matches a route to a request
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class CommandName extends Matcher
{
    /**
     * constructor
     * @param string $argumentName
     */
    public function __construct(
        public string $argumentName
    )
    {
    }

    /**
     * attempts to match this path against the current request address
     * @param string $argumentName
     * @return bool
     */
    public function matches(Request $origin): bool
    {
        if (!($origin instanceof Command)) {
            return false;
        }
        return $origin->name === $this->argumentName;
    }
}