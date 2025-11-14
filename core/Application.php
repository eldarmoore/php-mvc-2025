<?php

namespace Core;

use Core\Container\Container;
use Core\Http\Request;
use Core\Http\Response;
use Core\Routing\Router;

/**
 * Application Class
 *
 * The main application class that bootstraps and runs the framework.
 * This is the central component that ties all other components together.
 */
class Application extends Container
{
    /**
     * The application instance (singleton)
     */
    protected static ?Application $instance = null;

    /**
     * Application configuration
     */
    protected array $config = [];

    /**
     * The router instance
     */
    protected Router $router;

    /**
     * The current request
     */
    protected ?Request $request = null;

    /**
     * Base path of the application
     */
    protected string $basePath;

    /**
     * Indicates if the application has been bootstrapped
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * Create a new application instance
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->basePath = BASE_PATH ?? dirname(__DIR__);
        $this->config = $config;

        static::$instance = $this;

        $this->registerBaseBindings();
        $this->bootstrap();
    }

    /**
     * Get the application instance
     *
     * @return static
     */
    public static function getInstance(): static
    {
        return static::$instance;
    }

    /**
     * Register base bindings in the container
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        $this->instance('app', $this);
        $this->instance(Application::class, $this);
        $this->instance(Container::class, $this);

        // Bind View service
        $this->singleton(\Core\View\View::class, function () {
            return new \Core\View\View();
        });

        // Bind CSRF service
        $this->singleton(\Core\Security\Csrf::class, function () {
            return new \Core\Security\Csrf();
        });
    }

    /**
     * Bootstrap the application
     *
     * @return void
     */
    protected function bootstrap(): void
    {
        if ($this->hasBeenBootstrapped) {
            return;
        }

        // Set timezone
        date_default_timezone_set($this->config['timezone'] ?? 'UTC');

        // Set error reporting based on debug mode
        if ($this->config['debug'] ?? false) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize database
        if (file_exists(CONFIG_PATH . '/database.php')) {
            $dbConfig = require CONFIG_PATH . '/database.php';
            \Core\Database\Database::init($dbConfig);
        }

        // Create and bind router
        $this->router = new Router();
        $this->singleton(Router::class, fn() => $this->router);

        // Load routes
        $this->loadRoutes();

        $this->hasBeenBootstrapped = true;
    }

    /**
     * Load application routes
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        $routesFile = $this->basePath . '/routes/web.php';

        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }

    /**
     * Run the application
     *
     * @return void
     */
    public function run(): void
    {
        // Capture the request
        $this->request = Request::capture();

        // Dispatch the request through the router
        $response = $this->router->dispatch($this->request);

        // Send the response
        $response->send();
    }

    /**
     * Get the router instance
     *
     * @return Router
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Get the current request
     *
     * @return Request|null
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * Get a configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get the base path of the application
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the application directory
     *
     * @param string $path
     * @return string
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath('app') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the config directory
     *
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the storage directory
     *
     * @param string $path
     * @return string
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the public directory
     *
     * @param string $path
     * @return string
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Determine if the application is in debug mode
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->config['debug'] ?? false;
    }

    /**
     * Get the environment the application is running in
     *
     * @return string
     */
    public function environment(): string
    {
        return $this->config['env'] ?? 'production';
    }

    /**
     * Determine if the application is in the local environment
     *
     * @return bool
     */
    public function isLocal(): bool
    {
        return $this->environment() === 'local' || $this->environment() === 'development';
    }

    /**
     * Determine if the application is in the production environment
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }
}
