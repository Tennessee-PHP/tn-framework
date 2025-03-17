<?php

namespace TN\TN_Core\Attribute\Command;

use TN\TN_Core\Attribute\Route\Matcher;
use TN\TN_Core\Model\Request\Command;
use TN\TN_Core\Model\Request\Request;

/**
 * matches a route to a request
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class CLI
{
    /**
     * constructor
     * @param string $cliClass
     */
    public function __construct(
        public string $cliClass
    )
    {
    }
}