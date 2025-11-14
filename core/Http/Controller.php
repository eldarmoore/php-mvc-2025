<?php

namespace Core\Http;

use Core\View\View;
use Core\Validation\Validator;

/**
 * Base Controller Class
 *
 * All application controllers should extend this class.
 * Provides common functionality like view rendering, validation, etc.
 */
abstract class Controller
{
    /**
     * The request instance
     */
    protected Request $request;

    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        $this->request = app()->request();
    }

    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return Response
     */
    protected function view(string $view, array $data = []): Response
    {
        $content = view($view, $data);
        return Response::html($content);
    }

    /**
     * Return a JSON response
     *
     * @param mixed $data
     * @param int $status
     * @return Response
     */
    protected function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Redirect to a URL
     *
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Redirect back to the previous page
     *
     * @return Response
     */
    protected function back(): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this->redirect($referer);
    }

    /**
     * Validate request data
     *
     * @param array $rules
     * @param array $messages
     * @return array
     * @throws \Exception
     */
    protected function validate(array $rules, array $messages = []): array
    {
        $validator = new Validator($this->request->all(), $rules, $messages);

        if ($validator->fails()) {
            // Store errors in session
            $_SESSION['errors'] = $validator->errors();
            $_SESSION['_old_input'] = $this->request->all();

            // Redirect back with errors
            $this->back()->send();
            exit;
        }

        return $validator->validated();
    }

    /**
     * Get the authenticated user (if using authentication)
     *
     * @return mixed
     */
    protected function user()
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Require authentication (redirect if not authenticated)
     *
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login')->send();
            exit;
        }
    }

    /**
     * Flash a message to the session
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get a flashed message
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
