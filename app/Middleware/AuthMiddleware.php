<?php

namespace App\Middleware;

use Core\Http\Middleware;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Authentication Middleware
 *
 * Example middleware that checks if a user is authenticated.
 * Redirects to login if not authenticated.
 */
class AuthMiddleware implements Middleware
{
    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param callable $next
     * @return Response|null
     */
    public function handle(Request $request, callable $next): ?Response
    {
        // Check if user is authenticated (example using session)
        if (!isset($_SESSION['user_id'])) {
            // User is not authenticated, redirect to login
            return Response::redirect('/login');
        }

        // User is authenticated, continue to next middleware or controller
        return $next($request);
    }
}
