<?php

/**
 * Helper Functions
 *
 * Global helper functions available throughout the application.
 */

use Core\View\View;

if (!function_exists('env')) {
    /**
     * Get an environment variable value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // Handle boolean values
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value
     *
     * @param string $key (e.g., 'app.name' or 'database.default')
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        static $config = [];

        // Parse the key (e.g., 'app.name' => ['app', 'name'])
        $segments = explode('.', $key);
        $file = array_shift($segments);

        // Load config file if not already loaded
        if (!isset($config[$file])) {
            $path = CONFIG_PATH . '/' . $file . '.php';
            if (file_exists($path)) {
                $config[$file] = require $path;
            } else {
                return $default;
            }
        }

        // Navigate through nested array
        $value = $config[$file];
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('app')) {
    /**
     * Get the application instance or a service from the container
     *
     * @param string|null $service
     * @return mixed
     */
    function app(?string $service = null)
    {
        $app = \Core\Application::getInstance();

        if ($service === null) {
            return $app;
        }

        return $app->get($service);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    function view(string $view, array $data = []): string
    {
        return app(View::class)->render($view, $data);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response
     *
     * @param string $url
     * @param int $code
     * @return void
     */
    function redirect(string $url, int $code = 302): void
    {
        header("Location: $url", true, $code);
        exit;
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for the application
     *
     * @param string $path
     * @return string
     */
    function url(string $path = ''): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        $path = ltrim($path, '/');

        return $base . ($path ? '/' . $path : '');
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL for an asset
     *
     * @param string $path
     * @return string
     */
    function asset(string $path): string
    {
        return url($path);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die - useful for debugging
     *
     * @param mixed ...$vars
     * @return void
     */
    function dd(...$vars): void
    {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variable for debugging
     *
     * @param mixed ...$vars
     * @return void
     */
    function dump(...$vars): void
    {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value (from previous request)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, $default = null)
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token
     *
     * @return string
     */
    function csrf_token(): string
    {
        return app(\Core\Security\Csrf::class)->getToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token hidden input field
     *
     * @return string
     */
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities
     *
     * @param string $value
     * @return string
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage path
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return STORAGE_PATH . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the public path
     *
     * @param string $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return PUBLIC_PATH . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the base path
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}

if (!function_exists('logger')) {
    /**
     * Get a logger instance or log a message
     *
     * @param string|null $message
     * @param array $context
     * @param string $level
     * @param string $channel
     * @return \Core\Support\Logger|void
     */
    function logger(?string $message = null, array $context = [], string $level = 'info', string $channel = 'app')
    {
        static $loggers = [];

        // Get or create logger for channel
        if (!isset($loggers[$channel])) {
            $loggers[$channel] = new \Core\Support\Logger($channel);
        }

        $logger = $loggers[$channel];

        // If no message, return the logger instance
        if ($message === null) {
            return $logger;
        }

        // Log the message
        $logger->log($level, $message, $context);
    }
}

if (!function_exists('log_info')) {
    /**
     * Log an info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function log_info(string $message, array $context = []): void
    {
        logger($message, $context, 'info');
    }
}

if (!function_exists('log_error')) {
    /**
     * Log an error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function log_error(string $message, array $context = []): void
    {
        logger($message, $context, 'error');
    }
}

if (!function_exists('log_warning')) {
    /**
     * Log a warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function log_warning(string $message, array $context = []): void
    {
        logger($message, $context, 'warning');
    }
}

if (!function_exists('log_debug')) {
    /**
     * Log a debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function log_debug(string $message, array $context = []): void
    {
        logger($message, $context, 'debug');
    }
}
