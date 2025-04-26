declare global {
    interface Window {
        TN: {
            BASE_URL: string;
            CSS_URL: string;
            FONTS_CSS_URL: string;
            CLOUDFLARE_TURNSTILE_SITE_KEY: string;
            TINYMCE_BOOTSTRAP_KEY: string;
            FONT_URLS: string[];
            OUTLIER_ERROR_SETTINGS: any;
            OUTLIER_WARNING_SETTINGS: any;
            OUTLIER_POS_IGNORE_AFTER: Record<string, any>;
            [key: string]: any; // Allow for other dynamic properties
        }
    }

    // Also make TN available globally without window.
    const TN: Window['TN'];
}

// This file is a module
export {}; 