<?php

/**
 * MVC Framework Entry Point
 *
 * This file serves as the front controller for all requests.
 * All requests are routed through this file.
 */

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Load Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Load configuration
$config = require_once CONFIG_PATH . '/app.php';

// Initialize the application
$app = new Core\Application($config);

// Handle the request
$app->run();
