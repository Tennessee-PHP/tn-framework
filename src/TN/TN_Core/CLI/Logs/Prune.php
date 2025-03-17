<?php

namespace TN\TN_Core\CLI\Logs;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;

class Prune extends CLI
{
    public function run(): void
    {
        // use stack class to get all classes that use the MySQL prune trait
        foreach (Stack::getClassesInModuleNamespaces('Model', true, MySQLPrune::class) as $class) {
            try {
                $class::prune();
                $this->green("Pruned instances of {$class}");
            } catch (\Exception $e) {
                $this->red("Error pruning class {$class}: " . $e->getMessage());
            }
        }
    }
}