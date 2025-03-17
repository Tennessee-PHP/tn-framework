<?php

namespace TN\TN_Core\Model\Package;

use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Error\CodeException;

/**
 *
 */
class Package extends CodeContainer
{
    use ReadOnlyProperties;

    public string $name;
    public string $namespace;

    /** @var string[] */
    public array $modules = [];

    /** @var Package[] */
    protected static array $instances;

    protected function __construct() {
        parent::__construct();
        $this->namespace = $this->name;
    }

    /**
     * @param string $className
     * @return string|bool
     */
    public function resolveClassName(string $className): string|bool
    {
        $fullClassName = "{$this->name}\\{$className}";
        return class_exists($fullClassName) ? $fullClassName : false;
    }

    /**
     * create all the packages
     * @return void
     */
    protected static function instantiate(): void
    {
        $packageOrder = [];
        self::$instances = [];

        // iterate over all the directories in $_ENV['TN_PHP_ROOT']
        foreach (scandir($_ENV['TN_PHP_ROOT']) as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            // if there's a Package.php file in there, instantiate it
            if (!file_exists($_ENV['TN_PHP_ROOT'] . $dir . '/Package.php')) {
                continue;
            }

            $packageClassName = "\\{$dir}\\Package";

            self::$instances[] = new $packageClassName;
        }

        // let's now also add the TN package
        self::$instances[] = new \TN\Package;
    }

    public function getDir(): string
    {
        $reflection = new \ReflectionClass($this);
        $filename = $reflection->getFileName();
        if (!$filename) {
            throw new CodeException('Could not determine file location for package ' . get_class($this));
        }
        return dirname(dirname($filename)) . '/';
    }
}