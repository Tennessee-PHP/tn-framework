<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Command\CLI;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Command\Schedule;
use TN\TN_Core\Attribute\Route\Component;

class LogsController extends Controller {
    #[CommandName('logs/prune')]
    #[Schedule('30 5 * * *')]
    #[Component(\TN\TN_Core\CLI\Logs\Prune::class)]
    public function pruneLogs(): void {}
}