<?php

namespace Core\Container;

use ReflectionClass;
use ReflectionParameter;
use Exception;

/**
 * Dependency Injection Container
 *
 * A simple but powerful IoC container for managing class dependencies
 * and performing dependency injection with automatic resolution.
 */
class Container
{
    /**
     * Container bindings
     */
    protected array $bindings = [];

    /**
     * Singleton instances
     */
    protected array $instances = [];

    /**
     * Aliases for bindings
     */
    protected array $aliases = [];

    /**
     * Bind a class or interface to an implementation
     *
     * @param string $abstract
     * @param callable|string|null $concrete
     * @param bool $singleton
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton,
        ];
    }

    /**
     * Bind a class or interface as a singleton
     *
     * @param string $abstract
     * @param callable|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as a singleton
     *
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a class from the container
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Check for existing singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Resolve alias
        $abstract = $this->getAlias($abstract);

        // Get concrete implementation
        $concrete = $this->getConcrete($abstract);

        // Build the instance
        $object = $this->build($concrete, $parameters);

        // Store singleton if needed
        if (isset($this->bindings[$abstract]['singleton']) && $this->bindings[$abstract]['singleton']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete implementation for an abstract
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Build an instance of the given concrete type
     *
     * @param mixed $concrete
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    protected function build($concrete, array $parameters = [])
    {
        // If concrete is a closure, execute it
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }

        // Reflect on the class
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new Exception("Target class [{$concrete}] does not exist.");
        }

        // Check if class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target class [{$concrete}] is not instantiable.");
        }

        // Get the constructor
        $constructor = $reflector->getConstructor();

        // If no constructor, just instantiate
        if ($constructor === null) {
            return new $concrete;
        }

        // Get constructor parameters
        $dependencies = $constructor->getParameters();

        // Resolve dependencies
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all dependencies for a set of parameters
     *
     * @param array $dependencies
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // Check if parameter was explicitly provided
            if (isset($parameters[$dependency->getName()])) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            // Get the type hint
            $type = $dependency->getType();

            // If no type hint, check for default value
            if ($type === null) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve parameter [{$dependency->getName()}]");
                }
                continue;
            }

            // Handle built-in types
            if ($type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve built-in type parameter [{$dependency->getName()}]");
                }
                continue;
            }

            // Resolve class dependency
            $className = $type->getName();
            $results[] = $this->make($className);
        }

        return $results;
    }

    /**
     * Call a method with dependency injection
     *
     * @param callable|array|string $callback
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function call($callback, array $parameters = [])
    {
        if (is_string($callback) && str_contains($callback, '@')) {
            // Handle "Class@method" syntax
            [$class, $method] = explode('@', $callback, 2);
            $callback = [$this->make($class), $method];
        }

        if (is_array($callback)) {
            $reflector = new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_object($callback) && !$callback instanceof \Closure) {
            $reflector = new \ReflectionMethod($callback, '__invoke');
        } else {
            $reflector = new \ReflectionFunction($callback);
        }

        $dependencies = $reflector->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->invokeArgs(
            is_array($callback) ? $callback[0] : null,
            $instances
        );
    }

    /**
     * Get the alias for an abstract if available
     *
     * @param string $abstract
     * @return string
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Alias a type to a different name
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Check if a binding exists
     *
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Get a binding if it exists
     *
     * @param string $abstract
     * @return mixed
     */
    public function get(string $abstract)
    {
        return $this->make($abstract);
    }

    /**
     * Flush the container of all bindings and instances
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
    }
}
