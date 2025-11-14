<?php

namespace Core\Routing;

/**
 * Route Class
 *
 * Represents a single route with its URI pattern, HTTP method,
 * action, and middleware.
 */
class Route
{
    /**
     * The URI pattern
     */
    protected string $uri;

    /**
     * HTTP methods this route responds to
     */
    protected array $methods;

    /**
     * The route action (controller@method or closure)
     */
    protected $action;

    /**
     * Route parameters extracted from URI
     */
    protected array $parameters = [];

    /**
     * Middleware assigned to this route
     */
    protected array $middleware = [];

    /**
     * Route name
     */
    protected ?string $name = null;

    /**
     * Route prefix
     */
    protected string $prefix = '';

    /**
     * Compiled regex pattern for matching
     */
    protected ?string $compiledPattern = null;

    /**
     * Parameter names extracted from URI pattern
     */
    protected array $parameterNames = [];

    /**
     * Create a new route instance
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     */
    public function __construct(array $methods, string $uri, $action)
    {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $uri;
        $this->action = $action;
        $this->compilePattern();
    }

    /**
     * Check if the route matches the request
     *
     * @param string $uri
     * @param string $method
     * @return bool
     */
    public function matches(string $uri, string $method): bool
    {
        // Check HTTP method
        if (!in_array(strtoupper($method), $this->methods)) {
            return false;
        }

        // Clean URIs
        $uri = '/' . trim($uri, '/');
        $pattern = '/' . trim($this->prefix . $this->uri, '/');

        // Exact match for routes without parameters
        if ($pattern === $uri && strpos($pattern, '{') === false) {
            return true;
        }

        // Match using compiled regex pattern
        return (bool) preg_match($this->compiledPattern, $uri, $matches);
    }

    /**
     * Extract parameters from the URI
     *
     * @param string $uri
     * @return array
     */
    public function extractParameters(string $uri): array
    {
        $uri = '/' . trim($uri, '/');

        if (!preg_match($this->compiledPattern, $uri, $matches)) {
            return [];
        }

        // Remove the full match
        array_shift($matches);

        // Combine parameter names with values
        $parameters = [];
        foreach ($this->parameterNames as $index => $name) {
            if (isset($matches[$index])) {
                $parameters[$name] = $matches[$index];
            }
        }

        $this->parameters = $parameters;

        return $parameters;
    }

    /**
     * Compile the URI pattern to a regex
     *
     * @return void
     */
    protected function compilePattern(): void
    {
        $pattern = $this->prefix . $this->uri;
        $pattern = '/' . trim($pattern, '/');

        // Extract parameter names
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\??}/', $pattern, $matches);
        $this->parameterNames = $matches[1];

        // Convert {param} to named regex group
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $pattern);

        // Convert {param?} (optional) to optional regex group
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/', '([^/]*)', $pattern);

        // Escape forward slashes and compile
        $this->compiledPattern = '#^' . $pattern . '$#';
    }

    /**
     * Get the route URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the route methods
     *
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the route action
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the route parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set route middleware
     *
     * @param array|string $middleware
     * @return $this
     */
    public function middleware($middleware): static
    {
        $this->middleware = is_array($middleware) ? $middleware : func_get_args();
        return $this;
    }

    /**
     * Get route middleware
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Set route name
     *
     * @param string $name
     * @return $this
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get route name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set route prefix
     *
     * @param string $prefix
     * @return $this
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = '/' . trim($prefix, '/');
        $this->compilePattern();
        return $this;
    }

    /**
     * Get route prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Run the route action
     *
     * @return mixed
     */
    public function run()
    {
        if (is_callable($this->action)) {
            // Execute closure
            return call_user_func_array($this->action, array_values($this->parameters));
        }

        if (is_string($this->action) && str_contains($this->action, '@')) {
            // Execute controller@method
            [$controller, $method] = explode('@', $this->action);

            // Add namespace if not absolute
            if (!str_starts_with($controller, '\\')) {
                $controller = 'App\\Controllers\\' . $controller;
            }

            if (!class_exists($controller)) {
                throw new \Exception("Controller {$controller} not found");
            }

            $instance = app()->make($controller);

            if (!method_exists($instance, $method)) {
                throw new \Exception("Method {$method} not found in controller {$controller}");
            }

            return call_user_func_array([$instance, $method], array_values($this->parameters));
        }

        throw new \Exception('Invalid route action');
    }
}
