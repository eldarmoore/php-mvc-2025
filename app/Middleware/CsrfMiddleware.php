<?php

namespace App\Middleware;

use Core\Http\Middleware;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\Csrf;

/**
 * CSRF Middleware
 *
 * Validates CSRF tokens on POST, PUT, PATCH, and DELETE requests.
 */
class CsrfMiddleware implements Middleware
{
    /**
     * HTTP methods that require CSRF validation
     */
    protected array $except = [
        'GET',
        'HEAD',
        'OPTIONS',
    ];

    /**
     * URIs that should be excluded from CSRF verification
     */
    protected array $exceptUris = [
        // 'api/*',
    ];

    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param callable $next
     * @return Response|null
     */
    public function handle(Request $request, callable $next): ?Response
    {
        // Skip validation for excluded methods
        if (in_array($request->method(), $this->except)) {
            return $next($request);
        }

        // Skip validation for excluded URIs
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Validate CSRF token
        $csrf = new Csrf();
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

        if (!$token || !$csrf->validateToken($token)) {
            return Response::html('419 - CSRF Token Mismatch', 419);
        }

        return $next($request);
    }

    /**
     * Check if the request should skip CSRF validation
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->exceptUris as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if path matches pattern
     *
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern(string $path, string $pattern): bool
    {
        $pattern = str_replace('*', '.*', $pattern);
        return (bool) preg_match('#^' . $pattern . '$#', $path);
    }
}
