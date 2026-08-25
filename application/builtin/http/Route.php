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
 * File Name: application/builtin/http/Route.php
 * Version: 1.0.0
 * Description: HTTP Route definition with regex compilation and parameter binding.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

/**
 * Representation of a single route in the routing system.
 */
class Route
{
    /**
     * @var list<string>
     */
    protected array $methods;

    protected string $path;
    protected mixed $handler;
    protected ?string $name = null;

    /**
     * @var list<mixed>
     */
    protected array $middlewares = [];

    /**
     * @var array<string, string>
     */
    protected array $wheres = [];

    /**
     * @var array<string, mixed>
     */
    protected array $defaults = [];

    protected ?string $compiledRegex = null;

    /**
     * @var list<string>
     */
    protected array $paramNames = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * @param string|list<string> $methods
     * @param string $path
     * @param mixed $handler
     */
    public function __construct(string|array $methods, string $path, mixed $handler)
    {
        $this->methods = array_values(array_unique(array_map('strtoupper', (array) $methods)));
        $this->path = $this->normalizePath($path);
        $this->handler = $handler;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Sets the route name.
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Assigns middleware to the route.
     *
     * @param mixed|list<mixed> $middleware
     */
    public function middleware(mixed $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        foreach ($middlewares as $mw) {
            $this->middlewares[] = $mw;
        }
        return $this;
    }

    /**
     * Returns middleware assigned to the route.
     *
     * @return list<mixed>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Adds a regex parameter constraint.
     *
     * @param string|array<string, string> $name
     * @param ?string $pattern
     */
    public function where(string|array $name, ?string $pattern = null): static
    {
        if (is_array($name)) {
            foreach ($name as $paramName => $paramPattern) {
                $this->wheres[$paramName] = $paramPattern;
            }
        } elseif ($pattern !== null) {
            $this->wheres[$name] = $pattern;
        }

        $this->compiledRegex = null; // Force regex recompilation
        return $this;
    }

    /**
     * Sets a default parameter value.
     */
    public function default(string $name, mixed $value): static
    {
        $this->defaults[$name] = $value;
        return $this;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * @return list<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    /**
     * Checks if the route supports the specified HTTP method.
     */
    public function supportsMethod(string $method): bool
    {
        $methodUpper = strtoupper($method);
        return in_array('ANY', $this->methods, true) || in_array($methodUpper, $this->methods, true);
    }

    /**
     * Matches path against route pattern and returns extracted parameters or null.
     *
     * @return ?array<string, mixed>
     */
    public function match(string $path): ?array
    {
        $normalizedPath = $this->normalizePath($path);
        $regex = $this->getCompiledRegex();

        if (!preg_match($regex, $normalizedPath, $matches)) {
            return null;
        }

        $params = $this->defaults;

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Compiles route path pattern to PCRE regex.
     */
    public function getCompiledRegex(): string
    {
        if ($this->compiledRegex !== null) {
            return $this->compiledRegex;
        }

        $path = $this->path;
        $this->paramNames = [];
        $wildcardIndex = 0;

        // 1. Convert multi-segment wildcard /** to catch-all match
        $path = preg_replace_callback('#/\*\*#', function () use (&$wildcardIndex): string {
            $wildcardIndex++;
            $name = $wildcardIndex === 1 ? 'wildcard' : "wildcard_{$wildcardIndex}";
            $this->paramNames[] = $name;
            return sprintf('/(?P<%s>.+)', $name);
        }, $path);

        // 2. Convert single-segment wildcard /* to segment match
        $path = preg_replace_callback('#/\*#', function () use (&$wildcardIndex): string {
            $wildcardIndex++;
            $name = $wildcardIndex === 1 ? 'wildcard' : "wildcard_{$wildcardIndex}";
            $this->paramNames[] = $name;
            return sprintf('/(?P<%s>[^/]+)', $name);
        }, $path);

        // 3. Convert custom regex {param:regex} or standard {param} parameters
        $pattern = '#\{([a-zA-Z0-9_]+)(?::([^}]+))?\}#';
        $compiled = preg_replace_callback($pattern, function (array $matches): string {
            $name = $matches[1];
            $customRegex = $matches[2] ?? null;

            $this->paramNames[] = $name;

            if ($customRegex !== null && $customRegex !== '') {
                $regex = $customRegex;
            } elseif (isset($this->wheres[$name])) {
                $regex = $this->wheres[$name];
            } else {
                $regex = '[^/]+';
            }

            return sprintf('(?P<%s>%s)', $name, $regex);
        }, $path);

        $this->compiledRegex = '#^' . $compiled . '$#u';
        return $this->compiledRegex;
    }

    /**
     * Prepends prefix to route path (used when mounting sub-routers).
     */
    public function prependPrefix(string $prefix): static
    {
        $cleanPrefix = '/' . trim($prefix, '/');
        $cleanPath = '/' . trim($this->path, '/');

        if ($cleanPath === '/') {
            $this->path = $cleanPrefix === '/' ? '/' : $cleanPrefix;
        } else {
            $this->path = ($cleanPrefix === '/' ? '' : $cleanPrefix) . $cleanPath;
        }

        $this->compiledRegex = null;
        return $this;
    }

    /**
     * Prepends middlewares (higher priority).
     *
     * @param list<mixed> $middlewares
     */
    public function prependMiddlewares(array $middlewares): static
    {
        $this->middlewares = array_merge($middlewares, $this->middlewares);
        return $this;
    }

    protected function normalizePath(string $path): string
    {
        $trimmed = '/' . trim($path, '/');
        $clean = preg_replace('#/{2,}#', '/', $trimmed);
        return $clean === '' ? '/' : $clean;
    }
}
