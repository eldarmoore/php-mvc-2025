<?php

namespace Core\Http;

/**
 * HTTP Request Class
 *
 * Encapsulates all information about an HTTP request.
 * Provides convenient methods to access request data.
 */
class Request
{
    /**
     * Request URI
     */
    protected string $uri;

    /**
     * Request method (GET, POST, PUT, DELETE, etc.)
     */
    protected string $method;

    /**
     * Query parameters ($_GET)
     */
    protected array $query = [];

    /**
     * Request body parameters ($_POST)
     */
    protected array $request = [];

    /**
     * Uploaded files
     */
    protected array $files = [];

    /**
     * Server variables
     */
    protected array $server = [];

    /**
     * Request headers
     */
    protected array $headers = [];

    /**
     * Cookies
     */
    protected array $cookies = [];

    /**
     * Raw request body
     */
    protected ?string $content = null;

    /**
     * Create a new request instance
     *
     * @param array $query
     * @param array $request
     * @param array $files
     * @param array $cookies
     * @param array $server
     * @param string|null $content
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $files = [],
        array $cookies = [],
        array $server = [],
        ?string $content = null
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->server = $server;
        $this->content = $content;

        $this->method = $this->detectMethod();
        $this->uri = $this->detectUri();
        $this->headers = $this->extractHeaders();
    }

    /**
     * Create a request from PHP globals
     *
     * @return static
     */
    public static function capture(): static
    {
        return new static(
            $_GET ?? [],
            $_POST ?? [],
            $_FILES ?? [],
            $_COOKIE ?? [],
            $_SERVER ?? [],
            file_get_contents('php://input')
        );
    }

    /**
     * Get the request URI
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get the request path (URI without query string)
     *
     * @return string
     */
    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        return $path ? trim($path, '/') : '/';
    }

    /**
     * Get the request method
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Check if the request method matches
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    /**
     * Check if the request is a GET request
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if the request is a POST request
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if the request is a PUT request
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Check if the request is a DELETE request
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Check if the request is a PATCH request
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Check if the request is an AJAX request
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get an input value from the request
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        $input = array_merge($this->query, $this->request);

        // Handle JSON requests
        if ($this->isJson()) {
            $json = json_decode($this->content, true);
            if (is_array($json)) {
                $input = array_merge($input, $json);
            }
        }

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    /**
     * Get all input data
     *
     * @return array
     */
    public function all(): array
    {
        return $this->input();
    }

    /**
     * Get only specified keys from input
     *
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $input = $this->all();
        return array_intersect_key($input, array_flip($keys));
    }

    /**
     * Get all input except specified keys
     *
     * @param array $keys
     * @return array
     */
    public function except(array $keys): array
    {
        $input = $this->all();
        return array_diff_key($input, array_flip($keys));
    }

    /**
     * Check if input has a key
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Get a query parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a request parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(string $key, $default = null)
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * Get an uploaded file
     *
     * @param string $key
     * @return array|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if a file was uploaded
     *
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get a header value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get a cookie value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get the raw request body
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Check if the request expects JSON
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        return $this->isJson() || $this->wantsJson();
    }

    /**
     * Check if the request is JSON
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    /**
     * Check if the request wants JSON
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        $acceptable = $this->header('Accept', '');
        return str_contains($acceptable, '/json') || str_contains($acceptable, '+json');
    }

    /**
     * Get the client IP address
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        if (isset($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        } elseif (isset($this->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($this->server['REMOTE_ADDR'])) {
            return $this->server['REMOTE_ADDR'];
        }

        return null;
    }

    /**
     * Get the user agent
     *
     * @return string|null
     */
    public function userAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Detect the request method (including method spoofing)
     *
     * @return string
     */
    protected function detectMethod(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        // Support method spoofing via _method field
        if ($method === 'POST' && isset($this->request['_method'])) {
            $method = strtoupper($this->request['_method']);
        }

        return $method;
    }

    /**
     * Detect the request URI
     *
     * @return string
     */
    protected function detectUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Extract headers from server variables
     *
     * @return array
     */
    protected function extractHeaders(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = substr($key, 5);
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
