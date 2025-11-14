<?php

/**
 * Application Configuration
 *
 * This file contains the core configuration settings for the application.
 */

return [
    /**
     * Application Name
     */
    'name' => env('APP_NAME', 'MVC Framework'),

    /**
     * Application Environment
     * Options: 'development', 'production', 'testing'
     */
    'env' => env('APP_ENV', 'development'),

    /**
     * Debug Mode
     * When enabled, detailed error messages will be shown
     */
    'debug' => env('APP_DEBUG', true),

    /**
     * Application URL
     */
    'url' => env('APP_URL', 'http://localhost'),

    /**
     * Application Timezone
     */
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /**
     * Application Locale
     */
    'locale' => env('APP_LOCALE', 'en'),

    /**
     * Encryption Key
     * Used for encrypting session data and other sensitive information
     */
    'key' => env('APP_KEY', ''),

    /**
     * Service Providers
     * List of service providers to be loaded
     */
    'providers' => [
        // Add service providers here
    ],
];
