<?php

namespace TN\TN_Core\Component;

/**
 * Material Icon Generator for Smarty Templates
 * 
 * Generates Material Symbols icons with consistent styling
 */
class MaterialIconGenerator
{
    /**
     * Valid icon sizes mapping to Tailwind text classes
     */
    private static array $validSizes = [
        'sm' => 'text-sm',
        'base' => 'text-base', 
        'lg' => 'text-lg',
        'xl' => 'text-xl',
        '2xl' => 'text-2xl',
        '3xl' => 'text-3xl',
        '4xl' => 'text-4xl',
        '5xl' => 'text-5xl'
    ];

    /**
     * Generate Material Symbol icon HTML
     * 
     * @param array $params Parameters: name (required), size (optional), color (optional)
     * @return string Generated HTML
     */
    public static function generateIcon(array $params): string
    {
        // Validate required name parameter
        if (empty($params['name'])) {
            return '';
        }

        $name = htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8');
        
        // Handle size parameter
        $size = $params['size'] ?? 'lg'; // Default to lg
        $sizeClass = self::$validSizes[$size] ?? self::$validSizes['lg'];
        
        // Build classes array
        $classes = ['material-symbols-outlined', $sizeClass];
        
        // Handle color parameter using our text-colors system
        if (!empty($params['color'])) {
            $colorClass = TailwindClassGenerator::getTextColor($params['color']);
            if ($colorClass) {
                $classes[] = $colorClass;
            }
        }
        
        $classString = implode(' ', $classes);
        
        return "<span class=\"{$classString}\">{$name}</span>";
    }
}
