<?php

namespace Core\Security;

/**
 * CSRF Protection Class
 *
 * Provides Cross-Site Request Forgery protection by generating
 * and validating unique tokens for each session.
 */
class Csrf
{
    /**
     * Session key for storing CSRF token
     */
    protected string $sessionKey = '_csrf_token';

    /**
     * Form field name for CSRF token
     */
    protected string $formFieldName = '_token';

    /**
     * Generate a new CSRF token
     *
     * @return string
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey] = $token;

        return $token;
    }

    /**
     * Get the current CSRF token
     *
     * @return string
     */
    public function getToken(): string
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            return $this->generateToken();
        }

        return $_SESSION[$this->sessionKey];
    }

    /**
     * Validate a CSRF token
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            return false;
        }

        return hash_equals($_SESSION[$this->sessionKey], $token);
    }

    /**
     * Validate token from request
     *
     * @param array $requestData
     * @return bool
     */
    public function validateRequest(array $requestData): bool
    {
        $token = $requestData[$this->formFieldName] ?? '';

        return $this->validateToken($token);
    }

    /**
     * Get the form field name
     *
     * @return string
     */
    public function getFormFieldName(): string
    {
        return $this->formFieldName;
    }

    /**
     * Generate HTML for CSRF token field
     *
     * @return string
     */
    public function field(): string
    {
        $token = $this->getToken();
        $name = $this->formFieldName;

        return '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
