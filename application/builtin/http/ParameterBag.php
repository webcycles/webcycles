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
 * File Name: application/builtin/http/ParameterBag.php
 * Version: 1.0.0
 * Description: Key-value parameter container with typed accessors and filters.
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
 * Key-value parameter container (e.g. for $_GET, $_POST, request attributes).
 */
class ParameterBag implements Countable, IteratorAggregate
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(protected array $parameters = [])
    {
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns all parameters as associative array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Returns all parameter keys.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Returns a parameter value or default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Sets a parameter value.
     */
    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Checks if a parameter exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Removes a parameter.
     */
    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }

    /**
     * Replaces all parameters with a new array.
     *
     * @param array<string, mixed> $parameters
     */
    public function replace(array $parameters = []): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Merges parameters with given array.
     *
     * @param array<string, mixed> $parameters
     */
    public function merge(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Gets parameter as string.
     */
    public function getString(string $key, string $default = ''): string
    {
        $val = $this->get($key, $default);
        return is_scalar($val) ? (string) $val : $default;
    }

    /**
     * Gets parameter as integer.
     */
    public function getInt(string $key, int $default = 0): int
    {
        $val = $this->get($key, $default);
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
    public function getFloat(string $key, float $default = 0.0): float
    {
        $val = $this->get($key, $default);
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
    public function getBool(string $key, bool $default = false): bool
    {
        $val = $this->get($key, $default);
        if (is_bool($val)) {
            return $val;
        }
        if (is_scalar($val)) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }
        return $default;
    }

    /**
     * Gets parameter as array.
     *
     * @return array<mixed>
     */
    public function getArray(string $key, array $default = []): array
    {
        $val = $this->get($key, $default);
        return is_array($val) ? $val : $default;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns only the specified keys.
     *
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->parameters[$key];
            }
        }
        return $result;
    }

    /**
     * Returns all parameters except the specified keys.
     *
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $result = $this->parameters;
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        return $result;
    }

    /**
     * Filters parameters by callback.
     *
     * @param ?callable $callback
     * @return array<string, mixed>
     */
    public function filter(?callable $callback = null): array
    {
        return $callback !== null
            ? array_filter($this->parameters, $callback, ARRAY_FILTER_USE_BOTH)
            : array_filter($this->parameters);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    public function count(): int
    {
        return count($this->parameters);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->parameters);
    }
}
