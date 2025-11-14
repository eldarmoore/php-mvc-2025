<?php

namespace Core\Http;

/**
 * HTTP Response Class
 *
 * Encapsulates an HTTP response with status code, headers, and content.
 */
class Response
{
    /**
     * Response content
     */
    protected string $content;

    /**
     * HTTP status code
     */
    protected int $statusCode;

    /**
     * Response headers
     */
    protected array $headers = [];

    /**
     * HTTP status texts
     */
    protected static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Create a new response instance
     *
     * @param string $content
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Set the response content
     *
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the response status code
     *
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get the response status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a header
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Get a header value
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader(string $name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Check if a header exists
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * Remove a header
     *
     * @param string $name
     * @return $this
     */
    public function removeHeader(string $name): static
    {
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * Send the response to the client
     *
     * @return void
     */
    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Send the response headers
     *
     * @return void
     */
    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        // Send status line
        $statusText = static::$statusTexts[$this->statusCode] ?? 'Unknown';
        header("HTTP/1.1 {$this->statusCode} {$statusText}");

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", false);
        }
    }

    /**
     * Send the response content
     *
     * @return void
     */
    protected function sendContent(): void
    {
        echo $this->content;
    }

    /**
     * Create a JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @param array $headers
     * @return static
     */
    public static function json($data, int $statusCode = 200, array $headers = []): static
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $headers['Content-Type'] = 'application/json';

        return new static($json, $statusCode, $headers);
    }

    /**
     * Create an HTML response
     *
     * @param string $html
     * @param int $statusCode
     * @param array $headers
     * @return static
     */
    public static function html(string $html, int $statusCode = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';

        return new static($html, $statusCode, $headers);
    }

    /**
     * Create a redirect response
     *
     * @param string $url
     * @param int $statusCode
     * @return static
     */
    public static function redirect(string $url, int $statusCode = 302): static
    {
        $response = new static('', $statusCode);
        $response->setHeader('Location', $url);

        return $response;
    }

    /**
     * Create a download response
     *
     * @param string $content
     * @param string $filename
     * @param array $headers
     * @return static
     */
    public static function download(string $content, string $filename, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/octet-stream';
        $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
        $headers['Content-Length'] = strlen($content);

        return new static($content, 200, $headers);
    }

    /**
     * Create a 404 Not Found response
     *
     * @param string $message
     * @return static
     */
    public static function notFound(string $message = 'Not Found'): static
    {
        return static::html($message, 404);
    }

    /**
     * Create a 403 Forbidden response
     *
     * @param string $message
     * @return static
     */
    public static function forbidden(string $message = 'Forbidden'): static
    {
        return static::html($message, 403);
    }

    /**
     * Create a 401 Unauthorized response
     *
     * @param string $message
     * @return static
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return static::html($message, 401);
    }

    /**
     * Create a 500 Internal Server Error response
     *
     * @param string $message
     * @return static
     */
    public static function error(string $message = 'Internal Server Error'): static
    {
        return static::html($message, 500);
    }

    /**
     * Convert the response to a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
