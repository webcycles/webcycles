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
 * File Name: application/builtin/http/Response.php
 * Version: 1.0.0
 * Description: Base HTTP response object handling status codes, headers, cookies and body.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

use InvalidArgumentException;

/**
 * Base HTTP response representation.
 */
class Response
{
    protected int $statusCode;
    protected string $statusText;
    public HeaderBag $headers;
    protected ?string $content;
    protected string $version = '1.1';

    /**
     * @var array<string, Cookie>
     */
    protected array $cookies = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * @param ?string $content Response body content
     * @param int $status HTTP status code (default 200 OK)
     * @param array<string, string|list<string>> $headers Response headers
     */
    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        $this->headers = new HeaderBag($headers);
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->ensureDefaultHeaders();
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Sets the response body content.
     */
    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Returns the response body content.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the HTTP status code and optional reason phrase.
     */
    public function setStatusCode(int $code, ?string $text = null): static
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP status code: %d.', $code));
        }

        $this->statusCode = $code;
        $this->statusText = $text ?? HttpStatus::getReasonPhrase($code);
        return $this;
    }

    /**
     * Returns the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the HTTP status reason phrase.
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * Sets HTTP protocol version (e.g. 1.0, 1.1, 2.0).
     */
    public function setProtocolVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Sets or adds a response header.
     */
    public function header(string $name, string|array $value, bool $replace = true): static
    {
        $this->headers->set($name, $value, $replace);
        return $this;
    }

    /**
     * Attaches a cookie to the response.
     */
    public function withCookie(
        Cookie|string $cookie,
        ?string $value = null,
        int $expire = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        ?string $sameSite = Cookie::SAME_SITE_LAX
    ): static {
        if (is_string($cookie)) {
            $cookie = new Cookie($cookie, $value, $expire, $path, $domain, $secure, $httpOnly, false, $sameSite);
        }

        $this->cookies[$cookie->getName()] = $cookie;
        return $this;
    }

    /**
     * Removes a cookie on the client side.
     */
    public function withoutCookie(string $name, string $path = '/', ?string $domain = null): static
    {
        $this->cookies[$name] = Cookie::forget($name, $path, $domain);
        return $this;
    }

    /**
     * Returns all attached cookies.
     *
     * @return array<string, Cookie>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Sends HTTP status code, headers and cookies to the client.
     */
    public function sendHeaders(): static
    {
        if (headers_sent()) {
            return $this;
        }

        // 1. Status Code
        http_response_code($this->statusCode);

        // 2. HTTP Headers
        foreach ($this->headers->toHeaderLines() as $headerLine) {
            header($headerLine, false, $this->statusCode);
        }

        // 3. Cookies
        foreach ($this->cookies as $cookie) {
            header('Set-Cookie: ' . $cookie->toHeaderString(), false);
        }

        return $this;
    }

    /**
     * Sends response body to the client.
     */
    public function sendContent(): static
    {
        if ($this->content !== null && $this->content !== '') {
            echo $this->content;
        }

        return $this;
    }

    /**
     * Sends full response (headers + body) and flushes output.
     */
    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }

        return $this;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    protected function ensureDefaultHeaders(): void
    {
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'text/html; charset=UTF-8');
        }
    }
}
