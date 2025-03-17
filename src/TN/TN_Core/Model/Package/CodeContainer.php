<?php

namespace TN\TN_Core\Model\Package;

use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;

/**
 *
 */
class CodeContainer
{
    use ReadOnlyProperties;

    public string $name;
    public string $namespace;

    /** @var static[] */
    protected static array $instances;

    protected function __construct() {}

    /**
     * create all the packages
     * @return void
     */
    protected static function instantiate(): void
    {
        static::$instances = [];
    }

    /**
     * @return static[]
     */
    public static function getAll(): array
    {
        if (!isset(static::$instances)) {
            static::instantiate();
        }
        return static::$instances;
    }

    public static function get(string $name): ?CodeContainer
    {
        if (!isset(static::$instances)) {
            static::instantiate();
        }
        foreach (static::$instances as $instance) {
            if ($instance->name === $name || $instance instanceof $name) {
                return $instance;
            }
        }
        return null;
    }

    public function getDir(): string
    {
        return $_ENV['TN_PHP_ROOT'];
    }

    /**
     * return non-abstract class names in a sub-namespace
     * @param string $namespace e.g. "Model" - will look for PKG\Model\* classes
     * @param string $withTrait
     * @param string $subClassOf
     * @return array
     */
    public function getClassesInNamespace(string $namespace, string $withTrait = '', string $subClassOf = ''): array
    {
        $dir = $this->getDir() . str_replace('\\', '/', $namespace) . '/';
        if (!is_dir($dir)) {
            return [];
        }

        $classes = [];

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $origFile = $file;
            $file = $dir . $file;

            if (is_dir($file)) {
                $classes = array_merge($classes, $this->getClassesInNamespace($namespace . '\\' . $origFile, $withTrait, $subClassOf));
                continue;
            }

            if (!str_ends_with($file, '.php')) {
                continue;
            }
            $baseDir = $this->getDir();
            $class = $this->namespace . '\\' . str_replace('/', '\\', substr($file, strlen($baseDir), -4));
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException) {
                continue;
            }

            if ($reflection->isAbstract()) {
                continue;
            }

            if (!empty($withTrait)) {
                $hasTrait = false;
                $traitCheckerReflection = $reflection;
                do {
                    if (in_array($withTrait, $traitCheckerReflection->getTraitNames())) {
                        $hasTrait = true;
                        break;
                    }
                } while ($traitCheckerReflection = $traitCheckerReflection->getParentClass());

                if (!$hasTrait) {
                    continue;
                }
            }

            if (!empty($subClassOf) && !$reflection->isSubclassOf($subClassOf)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }
}