<?php

namespace TN\TN_Core\CLI\CronTab;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Package\Stack;

class All extends CLI
{

    public function run(): void
    {
        $crons = [];
        foreach (Stack::getChildClasses(Controller::class) as $controllerClassName) {
            $controller = new $controllerClassName;
            $crons = array_merge($crons, $controller->getCronTab());
        }
        $this->out(implode(PHP_EOL, $crons));
    }
}