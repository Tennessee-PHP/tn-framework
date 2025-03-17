<?php

namespace TN\TN_Core\Error\Access;

use TN\TN_Core\Error\Access\AccessException;

class FullPageRoadblockException extends AccessException
{
    use \TN\TN_Core\Trait\Getter;
}
