<?php

namespace TN\TN_Core\Model\Package;

use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Error\CodeException;

class Module extends CodeContainer
{
    use ReadOnlyProperties;

    public string $package;

    /** @var Module[] */
    protected static array $instances;

    /** @var string[] */
    public array $moduleDependencies = [];

    protected function __construct() {
        parent::__construct();
        $parts = explode('_', $this->name);
        $this->namespace = $parts[0] . '\\' . $this->name;
    }

    public function getDir(): string
    {
        $reflection = new \ReflectionClass($this);
        $filename = $reflection->getFileName();
        if (!$filename) {
            throw new CodeException('Could not determine file location for module ' . get_class($this));
        }
        return dirname($filename) . '/';
    }

    private static function instantiateModule(string $moduleClassName): void
    {
        // if we already have an instance of it, don't do anything
        foreach (self::$instances as $module) {
            if ($module instanceof $moduleClassName) {
                return;
            }
        }

        // instantiate it
        $module = new $moduleClassName;
        self::$instances[] = $module;

        // now do its dependencies if they're not there already
        foreach ($module->moduleDependencies as $moduleDependencyClassName) {
            self::instantiateModule($moduleDependencyClassName);
        }

    }

    protected static function instantiate(): void
    {
        self::$instances = [];
        foreach (Package::getAll() as  $package) {
            foreach ($package->modules as $moduleClassName) {
                self::instantiateModule($moduleClassName);
            }
        }

    }
}