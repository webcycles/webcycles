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
 * File Name: application/builtin/http/HeaderBag.php
 * Version: 1.0.0
 * Description: HTTP header container with case-insensitivity and multi-value support.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * HTTP header container with case-insensitivity and multi-value support.
 */
class HeaderBag implements Countable, IteratorAggregate
{
    /**
     * @var array<string, list<string>>
     */
    protected array $headers = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns all headers as key => list of values array.
     *
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->headers;
    }

    /**
     * Returns all normalized header names.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->headers);
    }

    /**
     * Returns the first value of a header or the default value.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $normalized = $this->normalizeKey($key);
        if (!isset($this->headers[$normalized]) || empty($this->headers[$normalized])) {
            return $default;
        }

        return $this->headers[$normalized][0];
    }

    /**
     * Returns all values for a given header as an array.
     *
     * @return list<string>
     */
    public function allValues(string $key): array
    {
        $normalized = $this->normalizeKey($key);
        return $this->headers[$normalized] ?? [];
    }

    /**
     * Sets header value(s).
     *
     * @param string $key
     * @param string|list<string> $values
     * @param bool $replace If true, replaces existing values; otherwise appends.
     */
    public function set(string $key, string|array $values, bool $replace = true): void
    {
        $normalized = $this->normalizeKey($key);
        $valuesList = is_array($values) ? array_values($values) : [$values];

        // Cast values to strings and trim whitespace
        $stringValues = array_map(static fn($v): string => trim((string) $v), $valuesList);

        if ($replace || !isset($this->headers[$normalized])) {
            $this->headers[$normalized] = $stringValues;
        } else {
            $this->headers[$normalized] = array_merge($this->headers[$normalized], $stringValues);
        }
    }

    /**
     * Checks if a header exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($this->normalizeKey($key), $this->headers);
    }

    /**
     * Removes a header.
     */
    public function remove(string $key): void
    {
        unset($this->headers[$this->normalizeKey($key)]);
    }

    /**
     * Checks if a header contains a specific substring (e.g. 'application/json' in 'Accept').
     */
    public function contains(string $key, string $value): bool
    {
        $headerValue = $this->get($key);
        if ($headerValue === null) {
            return false;
        }

        return stripos($headerValue, $value) !== false;
    }

    /**
     * Extracts Bearer authorization token from Authorization header.
     */
    public function getBearerToken(): ?string
    {
        $auth = $this->get('Authorization') ?? $this->get('Http-Authorization') ?? $this->get('Redirect-Http-Authorization');
        if ($auth === null) {
            return null;
        }

        if (preg_match('/^\s*Bearer\s+(.+)$/i', $auth, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Normalizes header key to lowercase-with-dashes (e.g. 'content-type').
     */
    protected function normalizeKey(string $key): string
    {
        return strtolower(str_replace('_', '-', trim($key)));
    }

    /**
     * Returns headers as flat array of formatted header lines (e.g. for header() or curl).
     *
     * @return list<string>
     */
    public function toHeaderLines(): array
    {
        $lines = [];
        foreach ($this->headers as $key => $values) {
            $formattedKey = implode('-', array_map('ucfirst', explode('-', $key)));
            foreach ($values as $value) {
                $lines[] = "{$formattedKey}: {$value}";
            }
        }
        return $lines;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    public function count(): int
    {
        return count($this->headers);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->headers);
    }
}
