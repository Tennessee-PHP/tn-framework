<?php

namespace TN\TN_Core\Model\Package;

use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Error\CodeException;
use TN\TN_Core\Model\Package\Package;

/**
 * A Module represents a self-contained unit within a package that groups related functionality.
 * 
 * Modules provide:
 * - Logical grouping of components and features
 * - Dependency management between modules
 * - Namespace isolation
 * - Feature encapsulation
 *
 * Structure:
 * - Controller/: Route handlers and request processing
 * - Component/: UI components (see {@see \TN\TN_Core\Component\HTMLComponent})
 * - Model/: Business logic and data structures
 * - Command/: CLI commands and scheduled tasks
 * - View/: Templates and static assets
 *
 * Modules can depend on other modules within the same package or from lower packages,
 * ensuring proper loading order and dependency resolution.
 */
class Module extends CodeContainer
{
    use ReadOnlyProperties;

    public string $package;

    /** @var Module[] */
    protected static array $instances;

    /** @var string[] */
    public array $moduleDependencies = [];

    public function __construct(Package $package, string $name)
    {
        parent::__construct();
        $this->package = $package;
        $this->name = $name;
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