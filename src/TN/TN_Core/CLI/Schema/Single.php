<?php

namespace TN\TN_Core\CLI\Schema;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

class Single extends CLI
{
    public function run(): void
    {
        $classes = Stack::getClassesInModuleNamespaces('Model', true, MySQL::class);
        $class = $this->askMultipleChoiceQuestion('Select a class to get the schema for', $classes);
        if (!$class) {
            $this->red('Invalid selection');
            return;
        }
        $this->out($class::getSchema() . PHP_EOL);
    }
}