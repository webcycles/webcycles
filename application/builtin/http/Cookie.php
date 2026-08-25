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
 * File Name: application/builtin/http/Cookie.php
 * Version: 1.0.0
 * Description: HTTP Cookie representation and header builder (Cookie / Set-Cookie).
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Representation of an HTTP cookie (Cookie / Set-Cookie).
 */
class Cookie
{
    public const SAME_SITE_LAX = 'Lax';
    public const SAME_SITE_STRICT = 'Strict';
    public const SAME_SITE_NONE = 'None';

    protected string $name;
    protected ?string $value;
    protected int $expire;
    protected string $path;
    protected ?string $domain;
    protected bool $secure;
    protected bool $httpOnly;
    protected bool $raw;
    protected ?string $sameSite;

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    public function __construct(
        string $name,
        ?string $value = null,
        int|string|DateTimeInterface $expire = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        bool $raw = false,
        ?string $sameSite = self::SAME_SITE_LAX
    ) {
        if (empty($name) || preg_match('/[=,; \t\r\n\013\014]/', $name)) {
            throw new InvalidArgumentException(sprintf('Cookie name "%s" contains invalid characters.', $name));
        }

        $this->name = $name;
        $this->value = $value;
        $this->expire = self::normalizeExpire($expire);
        $this->path = empty($path) ? '/' : $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->raw = $raw;
        $this->sameSite = self::normalizeSameSite($sameSite);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Create a new Cookie instance.
     */
    public static function create(
        string $name,
        ?string $value = null,
        int|string|DateTimeInterface $expire = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        bool $raw = false,
        ?string $sameSite = self::SAME_SITE_LAX
    ): self {
        return new self($name, $value, $expire, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    /**
     * Create an expired Cookie instance for deletion.
     */
    public static function forget(string $name, string $path = '/', ?string $domain = null): self
    {
        return new self($name, null, time() - 31536000, $path, $domain);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getExpire(): int
    {
        return $this->expire;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function isRaw(): bool
    {
        return $this->raw;
    }

    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Converts the cookie to a Set-Cookie header string.
     * 
     * @return string
     */
    public function toHeaderString(): string
    {
        $val = (string) $this->value;
        $cookieValue = $this->raw ? $val : rawurlencode($val);

        $parts = [];
        if ($this->value === null || $this->value === '') {
            $parts[] = sprintf('%s=deleted; Expires=%s; Max-Age=0', $this->name, gmdate('D, d M Y H:i:s T', time() - 31536000));
        } else {
            $parts[] = sprintf('%s=%s', $this->name, $cookieValue);

            if ($this->expire > 0) {
                $parts[] = sprintf('Expires=%s', gmdate('D, d M Y H:i:s T', $this->expire));
                $parts[] = sprintf('Max-Age=%d', max(0, $this->expire - time()));
            }
        }

        if (!empty($this->path)) {
            $parts[] = sprintf('Path=%s', $this->path);
        }

        if (!empty($this->domain)) {
            $parts[] = sprintf('Domain=%s', $this->domain);
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($this->sameSite !== null) {
            $parts[] = sprintf('SameSite=%s', $this->sameSite);
        }

        return implode('; ', $parts);
    }

    /**
     * Sends the cookie via PHP header / setcookie.
     * 
     * @return bool
     */
    public function send(): bool
    {
        if (headers_sent()) {
            return false;
        }

        $options = [
            'expires' => $this->expire,
            'path' => $this->path,
            'domain' => $this->domain ?? '',
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
        ];

        if ($this->sameSite !== null) {
            $options['samesite'] = $this->sameSite;
        }

        if ($this->raw) {
            return setrawcookie($this->name, (string) $this->value, $options);
        }

        return setcookie($this->name, (string) $this->value, $options);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    private static function normalizeExpire(int|string|DateTimeInterface $expire): int
    {
        if ($expire instanceof DateTimeInterface) {
            return (int) $expire->format('U');
        }

        if (is_string($expire)) {
            $timestamp = strtotime($expire);
            if ($timestamp === false) {
                throw new InvalidArgumentException(sprintf('Invalid expiration date for cookie "%s".', $expire));
            }
            return $timestamp;
        }

        return $expire;
    }

    private static function normalizeSameSite(?string $sameSite): ?string
    {
        if ($sameSite === null) {
            return null;
        }

        $sameSiteLower = strtolower($sameSite);
        return match ($sameSiteLower) {
            'lax' => self::SAME_SITE_LAX,
            'strict' => self::SAME_SITE_STRICT,
            'none' => self::SAME_SITE_NONE,
            default => throw new InvalidArgumentException(sprintf('Invalid SameSite value: "%s". Allowed: Lax, Strict, None.', $sameSite)),
        };
    }
}
