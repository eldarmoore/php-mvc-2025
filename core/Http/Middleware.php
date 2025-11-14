<?php

namespace Core\Http;

/**
 * Middleware Interface
 *
 * All middleware must implement this interface.
 * Middleware can inspect and modify requests before they reach the controller.
 */
interface Middleware
{
    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param callable $next
     * @return Response|null
     */
    public function handle(Request $request, callable $next): ?Response;
}
