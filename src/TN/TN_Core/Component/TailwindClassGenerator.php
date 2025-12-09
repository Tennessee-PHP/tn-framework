<?php

namespace TN\TN_Core\Component;

/**
 * Tailwind Class Generator - Framework-level semantic component system
 * 
 * This class reads the project's tailwind.json file and uses it as a
 * translation layer to convert semantic color names to actual Tailwind classes.
 * 
 * Features:
 * - Reads colors, components, and sizes from tailwind.json
 * - Component class patterns (buttons, cards, inputs, etc.)
 * - Size variants and conditional logic
 * - Override capabilities for spacing, sizing, etc.
 * - Single source of truth for design tokens
 */
class TailwindClassGenerator
{
    private static ?array $config = null;

    /**
     * Load the TN configuration from tailwind.json
     */
    private static function loadConfig(): void
    {
        if (self::$config !== null) {
            return;
        }

        $configPath = $_ENV['TN_ROOT'] . 'src/css/tailwind.json';
        if (!file_exists($configPath)) {
            throw new \Exception("tailwind.json not found at: $configPath");
        }

        $configContent = file_get_contents($configPath);
        $config = json_decode($configContent, true);
        
        if ($config === null) {
            throw new \Exception("Invalid JSON in tailwind.json: " . json_last_error_msg());
        }

        self::$config = $config;
    }

    /**
     * Translate semantic color names to actual Tailwind classes
     */
    private static function translateColors(string $classes): string
    {
        self::loadConfig();
        
        if (!isset(self::$config['colors'])) {
            return $classes;
        }
        
        $colors = self::$config['colors'];
        
        // Replace {colorName} placeholders with actual Tailwind classes
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($colors) {
            $colorName = $matches[1];
            return $colors[$colorName] ?? $colorName;
        }, $classes);
    }

    /**
     * Main function for generating Tailwind classes
     * Called from Smarty templates via {tw ...} function
     * 
     * @param array $params Parameters from Smarty template
     * @return string Generated Tailwind classes
     */
    public static function generateClasses(array $params): string
    {
        self::loadConfig();
        
        $classes = [];
        
        // Handle component patterns
        if (isset($params['component'])) {
            $componentClasses = self::getComponent($params['component']);
            if ($componentClasses) {
                $classes[] = $componentClasses;
            }
        }
        
        // Handle text colors
        if (isset($params['text_color'])) {
            $textColorClasses = self::getTextColor($params['text_color']);
            if ($textColorClasses) {
                $classes[] = $textColorClasses;
            }
        }
        
        // Handle individual color
        if (isset($params['color'])) {
            $classes[] = self::getColor($params['color']);
        }
        
        // Handle size variants
        if (isset($params['size']) && isset($params['component'])) {
            $baseComponent = explode('-', $params['component'])[0];
            $sizeClasses = self::getSize($baseComponent, $params['size']);
            if ($sizeClasses) {
                $classes[] = $sizeClasses;
            }
        }
        
        // Handle custom overrides
        foreach (['padding', 'margin', 'text', 'bg', 'border', 'custom'] as $prop) {
            if (isset($params[$prop])) {
                $classes[] = $params[$prop];
            }
        }
        
        // Handle conditional classes
        if (isset($params['if']) && isset($params['then'])) {
            if ($params['if']) {
                $classes[] = $params['then'];
            }
        }
        
        $result = implode(' ', array_filter($classes));
        
        // Translate any semantic color placeholders to actual Tailwind classes
        return self::translateColors($result);
    }

    /**
     * Get a component class set
     * Called from Smarty templates via {'button-primary'|tw_component}
     * 
     * @param string $componentName Component name
     * @return string Component classes
     */
    public static function getComponent(string $componentName): string
    {
        self::loadConfig();
        
        if (!isset(self::$config['components'][$componentName])) {
            return '';
        }
        
        $classes = self::$config['components'][$componentName];
        return self::translateColors($classes);
    }

    /**
     * Get a color value
     * Called from Smarty templates via {'primary'|tw_color}
     * 
     * @param string $colorName Color name
     * @return string Color value
     */
    public static function getColor(string $colorName): string
    {
        self::loadConfig();
        
        return self::$config['colors'][$colorName] ?? $colorName;
    }

    /**
     * Get a text color class set
     * Called from Smarty templates via {'text-role-admin'|tw_text_color}
     * 
     * @param string $textColorName Text color name
     * @return string Text color classes
     */
    public static function getTextColor(string $textColorName): string
    {
        self::loadConfig();
        
        if (!isset(self::$config['text-colors'][$textColorName])) {
            return '';
        }
        
        $classes = self::$config['text-colors'][$textColorName];
        return self::translateColors($classes);
    }

    /**
     * Get size variant for a component type
     * 
     * @param string $componentType Component type (e.g., 'button', 'input')
     * @param string $size Size name (e.g., 'small', 'large')
     * @return string Size classes
     */
    public static function getSize(string $componentType, string $size): string
    {
        self::loadConfig();
        
        if (!isset(self::$config['sizes'][$componentType][$size])) {
            return '';
        }
        
        return self::$config['sizes'][$componentType][$size];
    }

    /**
     * Add or override a component
     * Allows projects to extend or customize components
     * 
     * @param string $name Component name
     * @param string $classes Component classes
     */
    public static function setComponent(string $name, string $classes): void
    {
        self::loadConfig();
        self::$config['components'][$name] = $classes;
    }

    /**
     * Add or override a color
     * Allows projects to extend or customize colors
     * 
     * @param string $name Color name
     * @param string $value Color value
     */
    public static function setColor(string $name, string $value): void
    {
        self::loadConfig();
        self::$config['colors'][$name] = $value;
    }

    /**
     * Get all available colors (for debugging/documentation)
     * 
     * @return array Available color mappings
     */
    public static function getAvailableColors(): array
    {
        self::loadConfig();
        return array_keys(self::$config['colors'] ?? []);
    }

    /**
     * Get all available components (for debugging/documentation)
     * 
     * @return array Available component mappings
     */
    public static function getAvailableComponents(): array
    {
        self::loadConfig();
        return array_keys(self::$config['components'] ?? []);
    }
}