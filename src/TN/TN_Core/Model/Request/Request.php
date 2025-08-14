<?php

namespace TN\TN_Core\Model\Request;

use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;

/**
 * an entry point into the framework: a request received from a protocol
 */
abstract class Request
{
    use ReadOnlyProperties;
    protected static ?Request $instance = null;

    /**
     * @param array $options map to properties
     */
    public function __construct(array $options = [])
    {
        // assign any options to properties, where possible
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param array $options
     * @return Request
     */
    public static function create(array $options = []): Request
    {
        $className = Stack::resolveClassName(static::class);
        static::$instance = new $className($options);
        return static::get();
    }

    /**
     * @return static|null
     */
    public static function get(): static
    {
        if (!static::$instance) {
            throw new \TN\TN_Core\Error\CodeException('Request not initialized');
        }
        return static::$instance;
    }

    /**
     * respond to the request
     * @return void
     */
    public abstract function respond(): void;
}
