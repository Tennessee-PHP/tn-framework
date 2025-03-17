<?php

namespace TN\TN_Core\Component;

use Smarty\Exception;
use Smarty\Smarty;
use TN\TN_Core\Model\Package\Package;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Package\Stack;

/**
 * A wrapper for a template engine - just needs to assign data to the template engine and render templates!
 *
 * Currently extends the Smarty template engine although could switch to extending a different template engine as
 * needed.
 *
 *
 */
class TemplateEngine extends Smarty
{
    /**
     * constructor
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct();
        $this->setTemplateDir($_ENV['TN_ROOT'] . 'src');
        foreach (Package::getAll() as $package) {
            $this->addTemplateDir($package->getDir());
        }
        $this->setCompileDir($_ENV['TN_TMP_ROOT'] . 'tpl_compile');
        $this->setConfigDir($_ENV['TN_PHP_ROOT'] . 'view/tpl/configs/');
        $this->setCacheDir($_ENV['TN_TMP_ROOT'] . 'tpl_compile');

        $this->registerPlugins();
    }

    protected function registerPlugins(): void
    {
        $this->registerPlugin('function', 'path', static::class . '::path');
        $this->registerPlugin('modifier', 'str_starts_with', static::class . '::strStartsWith');
        $this->registerPlugin('modifier', 'abs', static::class . '::abs');
        $this->registerPlugin('modifier', 'urlencode', static::class . '::urlencode');
        $this->registerPlugin('modifier', 'reset', static::class . '::reset');
    }

    public static function reset(array $array): string
    {
        return reset($array);
    }

    public static function getInstance(): TemplateEngine
    {
        return new (Stack::resolveClassName(static::class));
    }

    public static function strStartsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    public static function path(array $params): string
    {
        $parts = explode(':', $params['route']);
        if (count($parts) < 3) {
            return '';
        }
        return Controller::path($parts[0], $parts[1], $parts[2], $params);
    }

    public static function abs(mixed $value): int|float
    {
        return abs($value);
    }

    public static function urlencode(mixed $value): string
    {
        return urlencode($value);
    }

    /**
     * get data that should exist for every template
     * @return array[]
     */
    public function getBaseData(): array
    {
        // let's add the constants
        $envs = [
            'ENV',
            'BASE_URL',
            'STATIC_BASE_URL',
            'IMG_BASE_URL',
            'PLAYER_IMG_BASE_URL',
            'SUBSCRIBERS_BASE_URL',
            'COOKIE_DOMAIN',
            'SITE_NAME'
        ];
        $data = ['envList' => []];
        foreach ($envs as $env) {
            $data[$env] = $_ENV[$env];
            $data['envList'][$env] = $_ENV[$env];
        }

        return $data;
    }

    /**
     * add data to the template engine
     * @param $data
     */
    public function assignData($data): void
    {
        $this->clearAllAssign();
        $this->assign(array_merge($this->getBaseData(), $data));
    }
}
