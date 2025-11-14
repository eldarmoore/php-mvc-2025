<?php

namespace Core\View;

/**
 * View Class
 *
 * A simple template engine for rendering views with support for
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
     * The current layout being used
     */
    protected ?string $layout = null;

    /**
     * Sections defined in views
     */
    protected array $sections = [];

    /**
     * The current section being captured
     */
    protected ?string $currentSection = null;

    /**
     * Create a new view instance
     *
     * @param string|null $viewsPath
     */
    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? APP_PATH . '/Views';
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
        $path = $this->findView($view);

        if (!file_exists($path)) {
            throw new \Exception("View [{$view}] not found at path [{$path}]");
        }

        // Merge shared data with view data
        $data = array_merge($this->shared, $data);

        // Render the view
        $content = $this->renderView($path, $data);

        // If a layout is set, render within layout
        if ($this->layout !== null) {
            $layoutPath = $this->findView($this->layout);
            $this->sections['content'] = $content;
            $content = $this->renderView($layoutPath, $data);
            $this->layout = null;
            $this->sections = [];
        }

        return $content;
    }

    /**
     * Render a view file
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    protected function renderView(string $path, array $data): string
    {
        // Extract data to variables
        extract($data, EXTR_SKIP);

        // Start output buffering
        ob_start();

        // Include the view file
        include $path;

        // Get the buffered content
        return ob_get_clean();
    }

    /**
     * Find the view file path
     *
     * @param string $view
     * @return string
     */
    protected function findView(string $view): string
    {
        // Convert dot notation to path (e.g., "users.index" => "users/index")
        $view = str_replace('.', '/', $view);

        return $this->viewsPath . '/' . $view . '.php';
    }

    /**
     * Set the layout for the view
     *
     * @param string $layout
     * @return void
     */
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Start a section
     *
     * @param string $name
     * @return void
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End a section
     *
     * @return void
     */
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new \Exception('Cannot end section: no section started');
        }

        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    /**
     * Yield a section's content
     *
     * @param string $name
     * @param string $default
     * @return void
     */
    public function yield(string $name, string $default = ''): void
    {
        echo $this->sections[$name] ?? $default;
    }

    /**
     * Include a partial view
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    public function include(string $view, array $data = []): void
    {
        $path = $this->findView($view);

        if (!file_exists($path)) {
            throw new \Exception("Partial view [{$view}] not found");
        }

        extract(array_merge($this->shared, $data), EXTR_SKIP);
        include $path;
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
     * Escape HTML entities
     *
     * @param string|null $value
     * @return string
     */
    public function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Output escaped content
     *
     * @param string|null $value
     * @return void
     */
    public function e(?string $value): void
    {
        echo $this->escape($value);
    }

    /**
     * Check if a section exists
     *
     * @param string $name
     * @return bool
     */
    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    /**
     * Get a section's content
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }
}
