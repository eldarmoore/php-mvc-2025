<?php

namespace Core\Routing;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Router Class
 *
 * Manages application routes and dispatches requests to appropriate handlers.
 * Supports dynamic parameters, middleware, and route groups.
 */
class Router
{
    /**
     * Collection of registered routes
     */
    protected array $routes = [];

    /**
     * Named routes for easy URL generation
     */
    protected array $namedRoutes = [];

    /**
     * Current route group attributes
     */
    protected array $groupStack = [];

    /**
     * The current request instance
     */
    protected ?Request $request = null;

    /**
     * Middleware registry
     */
    protected array $middleware = [];

    /**
     * Register a GET route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function get(string $uri, $action): Route
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    /**
     * Register a POST route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function post(string $uri, $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * Register a PUT route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function put(string $uri, $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * Register a PATCH route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function patch(string $uri, $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    /**
     * Register a DELETE route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function delete(string $uri, $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * Register an OPTIONS route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function options(string $uri, $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * Register a route that responds to any HTTP verb
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function any(string $uri, $action): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * Register a route with multiple HTTP verbs
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function match(array $methods, string $uri, $action): Route
    {
        return $this->addRoute($methods, $uri, $action);
    }

    /**
     * Add a route to the collection
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    protected function addRoute(array $methods, string $uri, $action): Route
    {
        $route = new Route($methods, $uri, $action);

        // Apply group attributes
        if (!empty($this->groupStack)) {
            $this->applyGroupAttributes($route);
        }

        // Store the route
        foreach ($methods as $method) {
            $this->routes[$method][] = $route;
        }

        return $route;
    }

    /**
     * Create a route group with shared attributes
     *
     * @param array $attributes
     * @param callable $callback
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;

        call_user_func($callback, $this);

        array_pop($this->groupStack);
    }

    /**
     * Apply group attributes to a route
     *
     * @param Route $route
     * @return void
     */
    protected function applyGroupAttributes(Route $route): void
    {
        $attributes = end($this->groupStack);

        if (isset($attributes['prefix'])) {
            $route->prefix($attributes['prefix']);
        }

        if (isset($attributes['middleware'])) {
            $middleware = is_array($attributes['middleware'])
                ? $attributes['middleware']
                : [$attributes['middleware']];
            $route->middleware(...$middleware);
        }
    }

    /**
     * Dispatch the request to the appropriate route
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request): Response
    {
        $this->request = $request;

        $route = $this->findRoute($request);

        if ($route === null) {
            return Response::notFound('404 - Page Not Found');
        }

        // Extract route parameters
        $route->extractParameters($request->path());

        // Run middleware
        $response = $this->runMiddleware($route, $request);

        if ($response instanceof Response) {
            return $response;
        }

        // Execute the route action
        try {
            $result = $route->run();

            return $this->prepareResponse($result);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Find a route that matches the request
     *
     * @param Request $request
     * @return Route|null
     */
    protected function findRoute(Request $request): ?Route
    {
        $method = $request->method();
        $uri = $request->path();

        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $route) {
            if ($route->matches($uri, $method)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Run route middleware
     *
     * @param Route $route
     * @param Request $request
     * @return Response|null
     */
    protected function runMiddleware(Route $route, Request $request): ?Response
    {
        $middleware = $route->getMiddleware();

        foreach ($middleware as $name) {
            if (!isset($this->middleware[$name])) {
                throw new \Exception("Middleware {$name} not found");
            }

            $middlewareClass = $this->middleware[$name];
            $instance = new $middlewareClass();

            $result = $instance->handle($request, function ($request) {
                return null;
            });

            if ($result instanceof Response) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Register middleware
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    public function registerMiddleware(string $name, string $class): void
    {
        $this->middleware[$name] = $class;
    }

    /**
     * Register multiple middleware
     *
     * @param array $middleware
     * @return void
     */
    public function registerMiddlewareGroup(array $middleware): void
    {
        $this->middleware = array_merge($this->middleware, $middleware);
    }

    /**
     * Prepare the response
     *
     * @param mixed $result
     * @return Response
     */
    protected function prepareResponse($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        return new Response((string) $result);
    }

    /**
     * Handle exceptions
     *
     * @param \Exception $e
     * @return Response
     */
    protected function handleException(\Exception $e): Response
    {
        if (config('app.debug', false)) {
            $html = $this->renderExceptionDebug($e);
            return Response::html($html, 500);
        }

        return Response::error('500 - Internal Server Error');
    }

    /**
     * Render exception for debugging
     *
     * @param \Exception $e
     * @return string
     */
    protected function renderExceptionDebug(\Exception $e): string
    {
        $message = e($e->getMessage());
        $file = e($e->getFile());
        $line = $e->getLine();
        $trace = e($e->getTraceAsString());

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .error-container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                h1 { color: #e74c3c; margin-top: 0; }
                .error-details { background: #f8f9fa; padding: 15px; border-left: 4px solid #e74c3c; margin: 15px 0; }
                .trace { background: #2c3e50; color: #ecf0f1; padding: 15px; overflow-x: auto; }
                pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>Exception: {$message}</h1>
                <div class="error-details">
                    <strong>File:</strong> {$file}<br>
                    <strong>Line:</strong> {$line}
                </div>
                <h3>Stack Trace:</h3>
                <div class="trace">
                    <pre>{$trace}</pre>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get all registered routes
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Generate a URL for a named route
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function route(string $name, array $parameters = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route {$name} not found");
        }

        $route = $this->namedRoutes[$name];
        $uri = $route->getUri();

        // Replace parameters in URI
        foreach ($parameters as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
            $uri = str_replace('{' . $key . '?}', $value, $uri);
        }

        return url($uri);
    }
}
