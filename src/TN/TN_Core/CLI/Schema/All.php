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
            try {
                $reflection = new \ReflectionClass($class);
                if ($reflection->isAbstract()) {
                    continue;
                }
                try {
                    $schema = $class::getSchema();
                    if ($schema) {
                        $this->out($schema . PHP_EOL . PHP_EOL);
                    }
                } catch (\Exception $e) {
                    $this->red('schema error: ' . $e->getMessage());
                    continue;
                }
            } catch (\ReflectionException $e) {
                $this->red('reflection error: ' . $e->getMessage());
                continue;
            }
        }
    }
}
