<?php

namespace TN\TN_Core\CLI\Schema;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

class All extends CLI
{

    public function run(): void
    {
        $classes = Stack::getClassesInModuleNamespaces('Model', true, MySQL::class);
        foreach ($classes as $class) {
            $this->out($class::getSchema() . PHP_EOL . PHP_EOL);
        }
    }
}