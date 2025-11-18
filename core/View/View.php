<?php

namespace Core\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * View Class
 *
 * Template engine for rendering views using Twig with support for
 * layouts, sections, and template inheritance.
 */
class View
{
    /**
     * Path to views directory
     */
    protected string $viewsPath;

    /**
     * Shared data available to all views
     */
    protected array $shared = [];

    /**
     * Twig environment instance
     */
    protected Environment $twig;

    /**
     * Create a new view instance
     *
     * @param string|null $viewsPath
     */
    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? APP_PATH . '/Views';
        $this->initializeTwig();
    }

    /**
     * Initialize Twig environment
     *
     * @return void
     */
    protected function initializeTwig(): void
    {
        $loader = new FilesystemLoader($this->viewsPath);

        $config = config('view.twig', []);

        $this->twig = new Environment($loader, [
            'cache' => $config['cache'] ?? false,
            'debug' => $config['debug'] ?? false,
            'auto_reload' => $config['auto_reload'] ?? true,
            'strict_variables' => $config['strict_variables'] ?? false,
        ]);

        // Add custom functions
        $this->addTwigFunctions();
    }

    /**
     * Add custom Twig functions
     *
     * @return void
     */
    protected function addTwigFunctions(): void
    {
        // Add config() function
        $this->twig->addFunction(new TwigFunction('config', function ($key, $default = null) {
            return config($key, $default);
        }));

        // Add env() function
        $this->twig->addFunction(new TwigFunction('env', function ($key, $default = null) {
            return env($key, $default);
        }));

        // Add url() function
        $this->twig->addFunction(new TwigFunction('url', function ($path = '') {
            return url($path);
        }));

        // Add asset() function
        $this->twig->addFunction(new TwigFunction('asset', function ($path) {
            return asset($path);
        }));

        // Add csrf_field() function
        $this->twig->addFunction(new TwigFunction('csrf_field', function () {
            return csrf_field();
        }, ['is_safe' => ['html']]));

        // Add csrf_token() function
        $this->twig->addFunction(new TwigFunction('csrf_token', function () {
            return csrf_token();
        }));

        // Add old() function for form repopulation
        $this->twig->addFunction(new TwigFunction('old', function ($key, $default = '') {
            return $_SESSION['_old_input'][$key] ?? $default;
        }));

        // Add errors() function
        $this->twig->addFunction(new TwigFunction('errors', function ($key = null) {
            if ($key === null) {
                return $_SESSION['errors'] ?? [];
            }
            return $_SESSION['errors'][$key] ?? [];
        }));
    }

    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        // Convert dot notation to path (e.g., "users.index" => "users/index")
        $view = str_replace('.', '/', $view);

        // Add .twig extension
        $template = $view . '.twig';

        // Merge shared data with view data
        $data = array_merge($this->shared, $data);

        return $this->twig->render($template, $data);
    }

    /**
     * Share data with all views
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public function share($key, $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }
    }

    /**
     * Get the Twig environment instance
     *
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
