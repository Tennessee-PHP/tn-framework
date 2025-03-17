<?php

namespace TN\TN_Core\Error;

/**
 * an exception with input or output
 */
class IOException extends TNException
{
    public int $httpResponseCode = 400;
    public bool $messageIsUserFacing = false;
}
