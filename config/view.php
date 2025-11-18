<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Engine
    |--------------------------------------------------------------------------
    |
    | The template engine to use for rendering views.
    | Supported: "twig", "php"
    |
    */
    'engine' => 'twig',

    /*
    |--------------------------------------------------------------------------
    | Views Path
    |--------------------------------------------------------------------------
    |
    | The directory where your view templates are stored.
    |
    */
    'path' => APP_PATH . '/Views',

    /*
    |--------------------------------------------------------------------------
    | Twig Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Twig template engine.
    |
    */
    'twig' => [
        'cache' => BASE_PATH . '/storage/cache/views',
        'debug' => env('APP_DEBUG', false),
        'auto_reload' => env('APP_DEBUG', false),
        'strict_variables' => env('APP_DEBUG', false),
    ],
];
