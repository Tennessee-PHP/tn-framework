<?php

namespace TN\TN_Core\Component\Error\NotFoundError;

use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\Error\Error;
use TN\TN_Core\Component\Title\Title;

#[Page('Not Found', 'The requested page was not found', false)]
#[Route('TN_Core:Error:fileNotFound')]
class NotFoundError extends Error
{
}