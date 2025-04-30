<?php

namespace TN\TN_Core\Trait;
use TN\TN_Core\Model\Package\Stack;

/**
 * the singleton design pattern
 *
 * override the methods as necessary for invoking constructor with arguments
 * 
 */
trait ExtendedSingletons
{
    /** @var array instances of all the extended classes */
    private static array $instances = [];

    /**
     * protected constructor to enforce singleton
     */
    protected function __construct()
    {
    }

    /** @return mixed get a specific instance of whatever sub-class this is called from statically */
    public static function getInstance(): mixed
    {
        $class = get_called_class();
        foreach (self::$instances as $instance) {
            if ($instance instanceOf $class) {
                return $instance;
            }
        }
        $instance = new $class();
        self::$instances[] = $instance;
        return $instance;
    }

    /**
     * get a specific instance by its key
     * @param string $key
     * @return mixed
     */
    public static function getInstanceByKey(string $key): mixed
    {
        $instances = self::getInstances();
        foreach ($instances as $instance) {
            if ($instance->key === $key) {
                return $instance;
            }
        }
        return null;
    }

    /** @return array get instances of all that extend this class */
    public static function getInstances(): array
    {
        if (empty(self::$instances)) {
            foreach(Stack::getClassesInPackageNamespaces(self::getExtendedNamespace()) as
                    $class) {
                $class::getInstance();
            }
        }
        return self::$instances;
    }

    private static function getExtendedNamespace(): string
    {
        $parts = explode('\\', __CLASS__);

        // package off the start
        array_shift($parts);

        // actual class name off the end
        array_pop($parts);
        return implode('\\', $parts);
    }
}