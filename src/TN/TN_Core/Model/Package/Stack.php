<?php

namespace TN\TN_Core\Model\Package;

use TN\TN_Core\Model\Package\Package;

/**
 * helper for getInstance methods on TN\ classes
 *
 * This allows for other packages to subclass every TN\ class; as long as the class is named the same relative to the
 * base package, it will be invoked instead of the subclassed TN\ class. This isn't dissimilar from "overriding"!
 * 
 */
class Stack
{
    private static array $packages;

    /**
     * returns the resolved string of a class name or false if not found
     * @param string $className
     * @return string|bool
     */
    public static function resolveClassName(string $className): string|bool
    {
        $packageNames = [];
        foreach (Package::getAll() as $package) {
            $packageNames[] = $package->name;
        }

        // let's make sure we strip a package of the front, if it's there
        $classParts = explode('\\', $className);
        if (in_array($classParts[0], $packageNames)) {
            array_shift($classParts);
        }
        $className = implode('\\', $classParts);

        foreach (Package::getAll() as $package) {
            $resolved = $package->resolveClassName($className);
            if ($resolved !== false) {
                return $resolved;
            }
        }
        return false;
    }

    /**
     * look for all non-abstract classes defined in exactly this namespace inside each package
     * @param string $namespace
     * @param bool $unique will remove duplicates across packages, giving priority to the package higher in the stack
     * @param string $withTrait
     * @param string $subClassOf
     * @return string[]
     */
    public static function getClassesInPackageNamespaces(string $namespace, bool $unique = true, string $withTrait = '', string $subClassOf = ''): array
    {
        $classes = [];
        foreach (Package::getAll() as $pkg) {
            $classes = array_merge($classes, $pkg->getClassesInNamespace($namespace, $withTrait, $subClassOf));
        }

        if ($unique) {
            foreach ($classes as &$class) {
                $class = self::resolveClassName($class);
            }
            $classes = array_unique($classes);
        }

        return $classes;
    }

    /**
     * @param string $namespace
     * @param bool $unique
     * @param string $withTrait
     * @param string $subClassOf
     * @return array
     */
    public static function getClassesInModuleNamespaces(string $namespace, bool $unique = true, string $withTrait = '', string $subClassOf = ''): array
    {
        $classes = [];
        foreach (Module::getAll() as $module) {
            // todo: this module may be extended by other namespaces
            $classes = array_merge($classes, $module->getClassesInNamespace($namespace, $withTrait, $subClassOf));
        }

        if ($unique) {
            foreach ($classes as &$class) {
                $class = self::resolveClassName($class);
            }
            $classes = array_unique($classes);
        }

        return $classes;
    }

    /**
     * gets all child classes with package overwrites applied from all loaded modules
     * @param string $className
     * @return string[] numerative array of child class names
     */
    public static function getChildClasses(string $className): array
    {
        // we're going to get the namespace from the class
        $parts = explode('\\', $className);
        $package = array_shift($parts);
        $module = array_shift($parts);
        $class = array_pop($parts);
        $namespace = implode('\\', $parts);

        return self::getClassesInModuleNamespaces($namespace, true, '', $className);
    }
}