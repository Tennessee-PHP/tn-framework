<?php

namespace TN\TN_Core\Controller;

use TN\TN_Core\Attribute\Command\CLI;
use TN\TN_Core\Attribute\Command\CommandName;
use TN\TN_Core\Attribute\Route\Component;

class CronTabController extends Controller
{
    #[CommandName('crontab/generate')]
    #[Component(\TN\TN_Core\CLI\CronTab\All::class)]
    public function all(): void {}
}