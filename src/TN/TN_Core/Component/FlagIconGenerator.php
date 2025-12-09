<?php

namespace TN\TN_Core\Component;

/**
 * Flag Icon Generator for Smarty Templates
 * 
 * Generates flag icons using the flag-icons CSS library
 */
class FlagIconGenerator
{
    /**
     * Generate flag icon HTML
     * 
     * @param array $params Parameters: country (required)
     * @return string Generated HTML
     */
    public static function generateFlag(array $params): string
    {
        // Validate required country parameter
        if (empty($params['country'])) {
            return '';
        }

        $country = strtolower(htmlspecialchars($params['country'], ENT_QUOTES, 'UTF-8'));
        
        // Validate country code format (should be 2-3 characters)
        if (strlen($country) < 2 || strlen($country) > 3 || !ctype_alpha($country)) {
            return '';
        }
        
        // Include margin-right for spacing like the original
        return "<span class=\"fi fi-{$country} mr-1\"></span>";
    }
}
