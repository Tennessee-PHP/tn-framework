<?php

namespace TN\TN_Core\Component;

use Smarty\Exception;
use Smarty\Smarty;
use TN\TN_Core\Model\Package\Package;
use TN\TN_Core\Controller\Controller;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\User\User;

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
        $this->registerPlugin('modifier', 'urlencodeperiods', static::class . '::urlencodePeriods');
        $this->registerPlugin('modifier', 'fullurlencode', static::class . '::fullurlencode');
        $this->registerPlugin('modifier', 'reset', static::class . '::reset');
        $this->registerPlugin('modifier', 'substr', static::class . '::substr');
        $this->registerPlugin('modifier', 'strpos', static::class . '::strpos');
        $this->registerPlugin('modifier', 'array_search', static::class . '::arraySearch');
        $this->registerPlugin('modifier', 'strtoupper', static::class . '::strtoupper');
        $this->registerPlugin('modifier', 'timezone', static::class . '::timezone');
        $this->registerPlugin('function', 'icon', static::class . '::icon');
        
        // Tailwind class generation system
        $this->registerPlugin('function', 'tw', \TN\TN_Core\Component\TailwindClassGenerator::class . '::generateClasses');
        $this->registerPlugin('modifier', 'tw_color', \TN\TN_Core\Component\TailwindClassGenerator::class . '::getColor');
        $this->registerPlugin('modifier', 'tw_component', \TN\TN_Core\Component\TailwindClassGenerator::class . '::getComponent');
        $this->registerPlugin('modifier', 'tw_text_color', \TN\TN_Core\Component\TailwindClassGenerator::class . '::getTextColor');
        
        // Icon generation system
        $this->registerPlugin('function', 'icon_material', \TN\TN_Core\Component\MaterialIconGenerator::class . '::generateIcon');
        $this->registerPlugin('function', 'icon_flag', \TN\TN_Core\Component\FlagIconGenerator::class . '::generateFlag');
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

    public static function urlencodePeriods(mixed $value): string
    {
        return str_replace('.', '%2E', (string)$value);
    }

    public static function fullurlencode(mixed $value): string
    {
        // Convert to lowercase, replace spaces with dashes, remove special chars, and collapse multiple dashes
        return preg_replace('/-+/', '-', strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $value))));
    }

    public static function substr(string $string, int $start, ?int $length = null): string
    {
        return substr($string, $start, $length);
    }

    public static function strpos(string $haystack, string $needle): int|false
    {
        return strpos($haystack, $needle);
    }

    public static function arraySearch(mixed $needle, array $haystack): int|string|false
    {
        return array_search($needle, $haystack, true);
    }

    public static function strtoupper(?string $string): string
    {
        return $string === null ? '' : strtoupper($string);
    }

    /**
     * Convert a DateTime to a specific timezone and format it
     * Usage: {$event->start|timezone:'America/New_York':'M j, Y g:i A T'}
     * Usage: {$event->start|timezone:null:'M j, Y g:i A T'} (uses auto-detected timezone)
     * 
     * @param \DateTime|null $dateTime The datetime to convert
     * @param string|null $timezone Target timezone (if null, uses session timezone from JS detection or UTC)
     * @param string $format PHP date format string (default: 'M j, Y g:i A T')
     * @return string|null Formatted datetime string, or null if input is null
     */
    public static function timezone(?\DateTime $dateTime, ?string $timezone = null, string $format = 'M j, Y g:i A T'): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        // If no timezone specified, get from session (set by JavaScript auto-detection)
        if ($timezone === null) {
            $timezone = $_SESSION['user_timezone'] ?? 'UTC';
        }

        $converted = clone $dateTime;
        $converted->setTimezone(new \DateTimeZone($timezone));

        return $converted->format($format);
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

    /**
     * Generate a Font Awesome icon
     * @param array $params Parameters from Smarty template
     * @return string HTML for the icon
     */
    public static function icon(array $params): string
    {
        $name = $params['name'] ?? '';
        $style = $params['style'] ?? 'fas'; // fas, far, fab, fal, fat, fad
        $class = $params['class'] ?? '';
        $title = $params['title'] ?? '';

        if (empty($name)) {
            return '';
        }

        $iconClass = $style . ' fa-' . $name;
        if (!empty($class)) {
            $iconClass .= ' ' . $class;
        }

        $attributes = '';
        if (!empty($title)) {
            $attributes .= ' title="' . htmlspecialchars($title) . '"';
        }

        return '<i class="' . htmlspecialchars($iconClass) . '"' . $attributes . '></i>';
    }
}
