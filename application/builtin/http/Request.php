<?php

/*
 *     ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ    
 *  ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ 
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆ   ÆÆÆ   ÆÆÆ   ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆ                       
 * ÆÆÆÆÆÆÆÆÆ                        
 * ÆÆÆÆÆÆÆÆÆ                        
 * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ
 *  ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ 
 *     ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ   
 * 
 *             WebCycles
 * 
 * File Name: application/builtin/http/Request.php
 * Version: 1.0.0
 * Description: HTTP Request representation with auto-path resolution, proxy support, input and JSON parsing.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

use JsonException;

/**
 * Incoming HTTP Request representation.
 * Supports auto-detection of basePath and subdirectories (mod_rewrite / FastCGI / Shared Hosting),
 * trusted proxies, headers, files, cookies, method spoofing, and body payloads (JSON / Form).
 */
class Request
{
    public ParameterBag $query;
    public ParameterBag $request;
    public ParameterBag $attributes;
    public ParameterBag $cookies;
    public ParameterBag $files;
    public ParameterBag $server;
    public HeaderBag $headers;

    protected ?string $content = null;
    protected ?string $method = null;
    protected ?string $path = null;
    protected ?string $baseUrl = null;
    protected ?string $basePath = null;
    protected ?array $jsonPayload = null;

    /**
     * List of trusted proxy IP addresses (e.g. Cloudflare, Nginx reverse proxy).
     *
     * @var list<string>
     */
    protected static array $trustedProxies = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * @param array<string, mixed> $query $_GET
     * @param array<string, mixed> $request $_POST
     * @param array<string, mixed> $attributes Route parameters / custom attributes
     * @param array<string, mixed> $cookies $_COOKIE
     * @param array<string, mixed> $files $_FILES
     * @param array<string, mixed> $server $_SERVER
     * @param ?string $content Raw request body content
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ) {
        $this->query = new ParameterBag($query);
        $this->request = new ParameterBag($request);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new ParameterBag(UploadedFile::normalizeFiles($files));
        $this->server = new ParameterBag($server);
        $this->headers = new HeaderBag($this->extractHeadersFromServer($server));
        $this->content = $content;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Factory creating Request instance from PHP globals.
     */
    public static function createFromGlobals(): static
    {
        $server = $_SERVER;

        // Handle Apache / FastCGI authorization quirks
        if (!isset($server['HTTP_AUTHORIZATION'])) {
            if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
                $server['HTTP_AUTHORIZATION'] = $server['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (function_exists('apache_request_headers')) {
                $apacheHeaders = apache_request_headers();
                if (isset($apacheHeaders['Authorization'])) {
                    $server['HTTP_AUTHORIZATION'] = $apacheHeaders['Authorization'];
                } elseif (isset($apacheHeaders['authorization'])) {
                    $server['HTTP_AUTHORIZATION'] = $apacheHeaders['authorization'];
                }
            }
        }

        return new static(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $server,
            null
        );
    }

    /**
     * Creates a Request instance for simulated requests (e.g. testing).
     */
    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ): static {
        $uriParts = parse_url($uri);
        $queryString = $uriParts['query'] ?? '';
        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        $methodUpper = strtoupper($method);
        $serverDefaults = [
            'SERVER_NAME' => $uriParts['host'] ?? 'localhost',
            'SERVER_PORT' => $uriParts['port'] ?? (($uriParts['scheme'] ?? 'http') === 'https' ? 443 : 80),
            'HTTP_HOST' => $uriParts['host'] ?? 'localhost',
            'HTTP_USER_AGENT' => 'WebCycles/1.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => '/index.php',
            'REQUEST_METHOD' => $methodUpper,
            'REQUEST_URI' => ($uriParts['path'] ?? '/') . ($queryString !== '' ? '?' . $queryString : ''),
            'HTTPS' => ($uriParts['scheme'] ?? 'http') === 'https' ? 'on' : 'off',
        ];

        $mergedServer = array_merge($serverDefaults, $server);

        $request = in_array($methodUpper, ['POST', 'PUT', 'PATCH', 'DELETE'], true) ? $parameters : [];
        $mergedQuery = array_merge($query, $methodUpper === 'GET' ? $parameters : []);

        return new static(
            $mergedQuery,
            $request,
            [],
            $cookies,
            $files,
            $mergedServer,
            $content
        );
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Configures list of trusted proxy IP addresses.
     *
     * @param list<string> $proxies
     */
    public static function setTrustedProxies(array $proxies): void
    {
        self::$trustedProxies = $proxies;
    }

    /**
     * Checks if current connection is from a trusted proxy.
     */
    public function isFromTrustedProxy(): bool
    {
        $remoteAddr = $this->server->getString('REMOTE_ADDR');
        return in_array($remoteAddr, self::$trustedProxies, true);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns HTTP request method considering method spoofing (_method or X-HTTP-Method-Override).
     */
    public function getMethod(): string
    {
        if ($this->method !== null) {
            return $this->method;
        }

        $realMethod = $this->getRealMethod();

        if ($realMethod === 'POST') {
            $methodOverride = $this->headers->get('X-HTTP-Method-Override');
            if ($methodOverride === null) {
                $methodOverride = $this->request->get('_method');
            }

            if (is_string($methodOverride) && $methodOverride !== '') {
                $candidate = strtoupper($methodOverride);
                if (in_array($candidate, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
                    $this->method = $candidate;
                    return $this->method;
                }
            }
        }

        $this->method = $realMethod;
        return $this->method;
    }

    /**
     * Returns raw HTTP method from REQUEST_METHOD without spoofing.
     */
    public function getRealMethod(): string
    {
        $raw = $this->server->getString('REQUEST_METHOD', 'GET');
        return strtoupper(trim($raw));
    }

    /**
     * Checks if request method matches given string.
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Checks if connection is encrypted (HTTPS).
     */
    public function isSecure(): bool
    {
        if ($this->isFromTrustedProxy()) {
            $proto = $this->headers->get('X-Forwarded-Proto');
            if ($proto !== null) {
                return strtolower($proto) === 'https';
            }
        }

        $https = $this->server->get('HTTPS');
        if ($https !== null && $https !== '' && strtolower((string) $https) !== 'off') {
            return true;
        }

        $scheme = $this->server->getString('REQUEST_SCHEME');
        if (strtolower($scheme) === 'https') {
            return true;
        }

        return $this->server->getInt('SERVER_PORT') === 443;
    }

    /**
     * Returns scheme (http or https).
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Returns host name considering headers and trusted proxies.
     */
    public function getHost(): string
    {
        if ($this->isFromTrustedProxy()) {
            $forwardedHost = $this->headers->get('X-Forwarded-Host');
            if ($forwardedHost !== null) {
                $hosts = explode(',', $forwardedHost);
                return trim($hosts[0]);
            }
        }

        $host = $this->headers->get('Host');
        if ($host !== null) {
            return strtolower(explode(':', $host)[0]);
        }

        return $this->server->getString('SERVER_NAME', 'localhost');
    }

    /**
     * Returns request port number.
     */
    public function getPort(): int
    {
        if ($this->isFromTrustedProxy()) {
            $forwardedPort = $this->headers->get('X-Forwarded-Port');
            if ($forwardedPort !== null && is_numeric($forwardedPort)) {
                return (int) $forwardedPort;
            }
        }

        $host = $this->headers->get('Host');
        if ($host !== null && str_contains($host, ':')) {
            $parts = explode(':', $host);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                return (int) $parts[1];
            }
        }

        return $this->server->getInt('SERVER_PORT', $this->isSecure() ? 443 : 80);
    }

    /**
     * Returns scheme and host string (e.g. 'https://example.com' or 'http://localhost:8080').
     */
    public function getSchemeAndHttpHost(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $port = $this->getPort();

        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            return "{$scheme}://{$host}";
        }

        return "{$scheme}://{$host}:{$port}";
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Automatic detection of routing path (pathInfo / path).
     */
    public function path(): string
    {
        if ($this->path === null) {
            $this->calculatePathAndBasePath();
        }

        return $this->path;
    }

    public function pathInfo(): string
    {
        return $this->path();
    }

    /**
     * Calculates base path of subdirectory (e.g. /mysite or /mysite/public).
     */
    public function basePath(): string
    {
        if ($this->basePath === null) {
            $this->calculatePathAndBasePath();
        }

        return $this->basePath;
    }

    /**
     * Calculates request path and base path consistently.
     */
    protected function calculatePathAndBasePath(): void
    {
        if ($this->path !== null && $this->basePath !== null) {
            return;
        }

        $requestUri = $this->server->getString('REQUEST_URI', '/');
        $uriPath = rawurldecode(explode('?', $requestUri)[0]);
        $normalizedUri = '/' . trim($uriPath, '/');
        if ($normalizedUri === '') {
            $normalizedUri = '/';
        }

        $scriptName = str_replace('\\', '/', $this->server->getString('SCRIPT_NAME', ''));

        // Generate candidate prefixes from longest to shortest
        $candidates = [];
        if ($scriptName !== '') {
            $current = '/' . trim($scriptName, '/');
            while ($current !== '' && $current !== '/' && $current !== '.') {
                $candidates[] = $current;
                $parent = dirname($current);
                if ($parent === $current || $parent === '.' || $parent === '/' || $parent === '\\') {
                    break;
                }
                $current = str_replace('\\', '/', $parent);
            }
        }

        $matchedBase = '';
        $extractedPath = $normalizedUri;

        foreach ($candidates as $candidate) {
            if ($candidate === '/') {
                continue;
            }

            if ($normalizedUri === $candidate) {
                $matchedBase = $candidate;
                $extractedPath = '/';
                break;
            }

            if (str_starts_with($normalizedUri, $candidate . '/')) {
                $matchedBase = $candidate;
                $extractedPath = substr($normalizedUri, strlen($candidate));
                break;
            }
        }

        $this->basePath = $matchedBase;
        $this->path = $this->normalizePath($extractedPath);
    }

    /**
     * Returns base application URL with scheme, host and subdirectory.
     */
    public function baseUrl(): string
    {
        if ($this->baseUrl !== null) {
            return $this->baseUrl;
        }

        $this->baseUrl = $this->getSchemeAndHttpHost() . $this->basePath();
        return $this->baseUrl;
    }

    /**
     * Returns full request URL with query string.
     */
    public function fullUrl(): string
    {
        $query = $this->query->all();
        $queryString = http_build_query($query);
        $path = $this->path();
        
        $url = $this->baseUrl() . ($path === '/' ? '' : $path);

        if ($url === '') {
            $url = '/';
        }

        return $queryString !== '' ? "{$url}?{$queryString}" : $url;
    }

    /**
     * Returns request URL without query parameters.
     */
    public function url(): string
    {
        $path = $this->path();
        return $this->baseUrl() . ($path === '/' ? '' : $path);
    }

    /**
     * Returns URL path segments as 1-indexed array.
     *
     * @return array<int, string>
     */
    public function segments(): array
    {
        $segments = array_values(array_filter(explode('/', $this->path()), fn($s) => $s !== ''));
        $result = [];
        foreach ($segments as $i => $segment) {
            $result[$i + 1] = $segment;
        }
        return $result;
    }

    /**
     * Returns n-th path segment (1-indexed).
     */
    public function segment(int $index, ?string $default = null): ?string
    {
        $segments = $this->segments();
        return $segments[$index] ?? $default;
    }

    /**
     * Returns UTM marketing parameters.
     *
     * @return array<string, string>
     */
    public function utms(): array
    {
        $utms = [];
        foreach ($this->query->all() as $key => $value) {
            if (str_starts_with(strtolower((string) $key), 'utm_') && is_scalar($value)) {
                $utms[(string) $key] = (string) $value;
            }
        }
        return $utms;
    }

    /**
     * Gets a specific UTM parameter.
     */
    public function utm(string $key, ?string $default = null): ?string
    {
        $keyLower = strtolower($key);
        if (!str_starts_with($keyLower, 'utm_')) {
            $keyLower = 'utm_' . $keyLower;
        }

        $val = $this->query->get($keyLower);
        if ($val !== null && is_scalar($val)) {
            return (string) $val;
        }

        return $default;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns User-Agent header value.
     */
    public function userAgent(): ?string
    {
        return $this->headers->get('User-Agent');
    }

    /**
     * Returns client IP address considering trusted proxies.
     */
    public function ip(): ?string
    {
        if ($this->isFromTrustedProxy()) {
            $forwarded = $this->headers->get('X-Forwarded-For');
            if ($forwarded !== null) {
                $ips = explode(',', $forwarded);
                return trim($ips[0]);
            }

            $realIp = $this->headers->get('X-Real-IP');
            if ($realIp !== null) {
                return trim($realIp);
            }
        }

        return $this->server->getString('REMOTE_ADDR') ?: null;
    }

    /**
     * Extracts Bearer authorization token.
     */
    public function bearerToken(): ?string
    {
        return $this->headers->getBearerToken();
    }

    /**
     * Checks if request is an AJAX/Fetch XMLHttpRequest.
     */
    public function isAjax(): bool
    {
        return $this->headers->get('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Checks if client expects JSON response.
     */
    public function wantsJson(): bool
    {
        $accept = $this->headers->get('Accept', '');
        return str_contains($accept, 'application/json') || str_contains($accept, '+json');
    }

    /**
     * Checks if request body is JSON.
     */
    public function isJson(): bool
    {
        $contentType = $this->headers->get('Content-Type', '');
        return str_contains($contentType, 'application/json') || str_contains($contentType, '+json');
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns raw request body content.
     */
    public function getContent(): string
    {
        if ($this->content === null) {
            $raw = file_get_contents('php://input');
            $this->content = $raw !== false ? $raw : '';
        }

        return $this->content;
    }

    /**
     * Parses and returns JSON request body payload.
     *
     * @param ?string $key
     * @param mixed $default
     * @return mixed
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonPayload === null) {
            $raw = $this->getContent();
            if (trim($raw) === '') {
                $this->jsonPayload = [];
            } else {
                try {
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
                    $this->jsonPayload = is_array($decoded) ? $decoded : [];
                } catch (JsonException) {
                    $this->jsonPayload = [];
                }
            }
        }

        if ($key === null) {
            return $this->jsonPayload;
        }

        return $this->jsonPayload[$key] ?? $default;
    }

    /**
     * Returns merged input data (query + request + json).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $json = is_array($this->json()) ? $this->json() : [];
        return array_merge($this->query->all(), $this->request->all(), $json);
    }

    /**
     * Gets input parameter from merged inputs or attributes.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $this->attributes->get($key, $default);
    }

    /**
     * Checks if input key exists.
     */
    public function has(string $key): bool
    {
        $all = $this->all();
        return array_key_exists($key, $all) || $this->attributes->has($key);
    }

    /**
     * Checks if input key exists and is non-empty.
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Returns only specified keys from input data.
     *
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }
        return $result;
    }

    /**
     * Returns all inputs except specified keys.
     *
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $result = $this->all();
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        return $result;
    }

    /**
     * Gets an input parameter from query, post, json, or attributes.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    /**
     * Gets a parameter from query string ($_GET).
     *
     * @param ?string $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query->all();
        }

        return $this->query->get($key, $default);
    }

    /**
     * Gets a parameter from POST body ($_POST).
     *
     * @param ?string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->request->all();
        }

        return $this->request->get($key, $default);
    }

    /**
     * Gets parameter as string.
     */
    public function str(string $key, string $default = ''): string
    {
        $val = $this->input($key, $default);
        return is_scalar($val) ? (string) $val : $default;
    }

    /**
     * Gets parameter as integer.
     */
    public function int(string $key, int $default = 0): int
    {
        $val = $this->input($key, $default);
        if (is_int($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return (int) $val;
        }
        return $default;
    }

    /**
     * Gets parameter as float.
     */
    public function float(string $key, float $default = 0.0): float
    {
        $val = $this->input($key, $default);
        if (is_float($val) || is_int($val)) {
            return (float) $val;
        }
        if (is_numeric($val)) {
            return (float) $val;
        }
        return $default;
    }

    /**
     * Gets parameter as boolean.
     */
    public function bool(string $key, bool $default = false): bool
    {
        $val = $this->input($key, $default);
        if (is_bool($val)) {
            return $val;
        }
        if (is_scalar($val)) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }
        return $default;
    }

    /**
     * Magic getter for input parameters ($request->name).
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->input($name);
    }

    /**
     * Magic isset for input parameters.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Sets custom request attribute.
     */
    public function withAttribute(string $key, mixed $value): static
    {
        $this->attributes->set($key, $value);
        return $this;
    }

    /**
     * Gets custom request attribute.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes->get($key, $default);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    protected function normalizePath(string $path): string
    {
        $trimmed = '/' . trim($path, '/');
        $clean = preg_replace('#/{2,}#', '/', $trimmed);
        return $clean === '' ? '/' : $clean;
    }

    /**
     * Extracts HTTP headers from $_SERVER array.
     *
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    protected function extractHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = str_replace('_', '-', $key);
                $headers[$name] = (string) $value;
            }
        }
        return $headers;
    }
}