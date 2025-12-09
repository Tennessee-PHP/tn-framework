<?php

namespace TN\TN_Core\Model\Request;

use Exception;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Package\Stack;

/**
 * a command run from the command line
 */
class Command extends Request
{
    /** @var string  */
    public string $name;

    /** @var string[] */
    public array $args = [];

    public bool $isCron = false;

    /**
     * @var Command|null static instance
     */
    protected static ?Request $instance = null;

    /**
     * Check if a Command is currently running without throwing an exception
     * @return bool
     */
    public static function isRunning(): bool
    {
        return static::$instance !== null;
    }

    /**
     * @param array $options
     * @throws Exception
     */
    protected function __construct(array $options = [])
    {
        parent::__construct($options);
        if (!empty($this->args[0]) && str_contains($this->args[0], 'run.php')) {
            array_shift($this->args);
        }

        if (empty($this->args)) {
            throw new Exception('No command name given');
        }

        $this->name = array_shift($this->args);
        foreach ($this->args as $arg) {
            if ($arg === '--cron') {
                $this->isCron = true;
            }
        }
    }

    /**
     * @return void
     */
    public function respond(): void
    {
        foreach (Stack::getChildClasses(Controller::class) as $controllerClassName) {
            $controller = new $controllerClassName;
            if ($response = $controller->run($this)) {
                break;
            }
        }
    }
}
