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
 * File Name: application/builtin/http/Router.php
 * Version: 1.0.0
 * Description: HTTP Router with sub-routing (mount), wildcard middlewares, and Reflection DI.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

use Closure;
use JsonSerializable;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use WebCycles\Foundations\HTTP\Exceptions\ControllerResolutionException;
use WebCycles\Foundations\HTTP\Exceptions\MethodNotAllowedException;
use WebCycles\Foundations\HTTP\Exceptions\RouteNotFoundException;
use stdClass;

/**
 * Advanced modular HTTP Router with sub-router mounting, wildcard middlewares,
 * automatic dependency injection (Reflection API) and precise HTTP exception handling.
 */
class Router
{
    /**
     * @var list<Route>
     */
    protected array $routes = [];

    /**
     * @var list<array{prefix: string, router: Router, middlewares: list<mixed>}>
     */
    protected array $mountedRouters = [];

    /**
     * @var list<array{pattern: string, middlewares: list<mixed>}>
     */
    protected array $patternMiddlewares = [];

    /**
     * @var list<mixed>
     */
    protected array $globalMiddlewares = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Registers a GET route.
     */
    public function get(string $path, mixed $handler): Route
    {
        return $this->match(['GET', 'HEAD'], $path, $handler);
    }

    /**
     * Registers a POST route.
     */
    public function post(string $path, mixed $handler): Route
    {
        return $this->match(['POST'], $path, $handler);
    }

    /**
     * Registers a PUT route.
     */
    public function put(string $path, mixed $handler): Route
    {
        return $this->match(['PUT'], $path, $handler);
    }

    /**
     * Registers a PATCH route.
     */
    public function patch(string $path, mixed $handler): Route
    {
        return $this->match(['PATCH'], $path, $handler);
    }

    /**
     * Registers a DELETE route.
     */
    public function delete(string $path, mixed $handler): Route
    {
        return $this->match(['DELETE'], $path, $handler);
    }

    /**
     * Registers an OPTIONS route.
     */
    public function options(string $path, mixed $handler): Route
    {
        return $this->match(['OPTIONS'], $path, $handler);
    }

    /**
     * Registers an ANY method route.
     */
    public function any(string $path, mixed $handler): Route
    {
        return $this->match(['ANY'], $path, $handler);
    }

    /**
     * Registers a route for specific HTTP methods.
     *
     * @param string|list<string> $methods
     */
    public function match(string|array $methods, string $path, mixed $handler): Route
    {
        $route = new Route($methods, $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Adds an existing Route object.
     */
    public function addRoute(Route $route): Route
    {
        $this->routes[] = $route;
        return $route;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Mounts a sub-router under the given URL prefix.
     * Cascades prefixes and middlewares into the sub-router routes.
     *
     * @param string $prefix URL prefix (e.g. '/admin', '/api/v1')
     * @param Router $router Sub-router instance
     * @param list<mixed> $middlewares Optional middlewares for the mounted sub-router
     */
    public function mount(string $prefix, Router $router, array $middlewares = []): static
    {
        $this->mountedRouters[] = [
            'prefix' => '/' . trim($prefix, '/'),
            'router' => $router,
            'middlewares' => array_values($middlewares),
        ];

        return $this;
    }

    /**
     * Registers global middleware or pattern-based wildcard middleware.
     *
     * Examples:
     * - $router->middleware([GlobalAuth::class])
     * - $router->middleware('/users/*', [AuthMiddleware::class])
     * - $router->middleware('/api/v1/**', [ApiKeyMiddleware::class, RateLimitMiddleware::class])
     *
     * @param string|list<mixed> $patternOrMiddlewares Path pattern or list of middlewares
     * @param list<mixed> $middlewares List of middlewares when first parameter is a path pattern
     */
    public function middleware(string|array $patternOrMiddlewares, array $middlewares = []): static
    {
        if (is_array($patternOrMiddlewares) && empty($middlewares)) {
            // Register global middlewares
            foreach ($patternOrMiddlewares as $mw) {
                $this->globalMiddlewares[] = $mw;
            }
            return $this;
        }

        if (is_string($patternOrMiddlewares)) {
            $pattern = trim($patternOrMiddlewares);
            if ($pattern === '' || $pattern === '*' || $pattern === '/*') {
                // Catch-all pattern - add to globals
                foreach ($middlewares as $mw) {
                    $this->globalMiddlewares[] = $mw;
                }
            } else {
                $this->patternMiddlewares[] = [
                    'pattern' => '/' . trim($pattern, '/'),
                    'middlewares' => array_values($middlewares),
                ];
            }
            return $this;
        }

        // Single global middleware passed as class/object
        $this->globalMiddlewares[] = $patternOrMiddlewares;
        return $this;
    }

    /**
     * Dispatches the current HTTP request and immediately sends the response to the client.
     * Catches HttpExceptions and formats them as proper responses.
     * 
     * @param ?Request $request Optional Request instance (defaults to Request::createFromGlobals())
     * @return Response
     */
    public function run(?Request $request = null): Response
    {
        $request ??= Request::createFromGlobals();

        try {
            $response = $this->dispatch($request);
        } catch (\WebCycles\Foundations\HTTP\Exceptions\HttpException $e) {
            $response = $request->wantsJson() ? $e->toJsonResponse() : $e->toResponse();
        } catch (\Throwable $e) {
            $status = 500;
            $message = $e->getMessage() ?: 'Internal Server Error';
            if ($request->wantsJson()) {
                $response = new JsonResponse(['error' => ['code' => $status, 'message' => $message]], $status);
            } else {
                $response = new Response(sprintf('<h1>%d Server Error</h1><p>%s</p>', $status, htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')), $status);
            }
        }

        return $response->send();
    }

    /**
     * Dispatches the incoming HTTP request through middleware pipeline to destination handler.
     *
     * @throws RouteNotFoundException When no route matches (HTTP 404)
     * @throws MethodNotAllowedException When HTTP method is not allowed (HTTP 405)
     */
    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $method = $request->getMethod();

        $allRoutes = $this->collectAllRoutes();
        $allowedMethods = [];
        $matchedRoute = null;
        $matchedParams = [];

        foreach ($allRoutes as $route) {
            $params = $route->match($path);
            if ($params !== null) {
                if ($route->supportsMethod($method)) {
                    $matchedRoute = $route;
                    $matchedParams = $params;
                    break;
                }

                // Route matches path but not method
                foreach ($route->getMethods() as $m) {
                    if ($m !== 'ANY') {
                        $allowedMethods[] = $m;
                    } else {
                        $allowedMethods = array_merge($allowedMethods, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD']);
                    }
                }
            }
        }

        if ($matchedRoute === null) {
            if (!empty($allowedMethods)) {
                throw new MethodNotAllowedException($allowedMethods, $method);
            }

            throw new RouteNotFoundException($path);
        }

        // Store route parameters and wildcards in request attributes
        foreach ($matchedParams as $paramKey => $paramValue) {
            $request->attributes->set($paramKey, $paramValue);
        }

        // Build full middleware list in order:
        // 1. Global
        // 2. Pattern-based
        // 3. Route-specific
        $pipelineMiddlewares = $this->resolveMiddlewaresForPath($path);
        foreach ($matchedRoute->getMiddlewares() as $routeMiddleware) {
            $pipelineMiddlewares[] = $routeMiddleware;
        }

        $destination = fn(Request $req): Response => $this->executeRouteHandler($matchedRoute, $req, $matchedParams);

        return (new Pipeline())
            ->send($request)
            ->through($pipelineMiddlewares)
            ->then($destination);
    }

    /**
     * Returns all registered routes (including unrolled sub-routers).
     *
     * @return list<Route>
     */
    public function getRoutes(): array
    {
        return $this->collectAllRoutes();
    }

    /**
     * Finds a route by name.
     */
    public function findRouteByName(string $name): ?Route
    {
        foreach ($this->collectAllRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }
        return null;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Recursively collects all routes from nested sub-routers applying prefixes and middlewares.
     *
     * @return list<Route>
     */
    protected function collectAllRoutes(string $parentPrefix = '', array $parentMiddlewares = []): array
    {
        $routes = [];

        // 1. Direct routes
        foreach ($this->routes as $route) {
            $cloned = clone $route;
            if ($parentPrefix !== '') {
                $cloned->prependPrefix($parentPrefix);
            }
            if (!empty($parentMiddlewares)) {
                $cloned->prependMiddlewares($parentMiddlewares);
            }
            $routes[] = $cloned;
        }

        // 2. Mounted sub-routers
        foreach ($this->mountedRouters as $mounted) {
            $combinedPrefix = $this->combinePrefixes($parentPrefix, $mounted['prefix']);
            $combinedMiddlewares = array_merge($parentMiddlewares, $mounted['middlewares']);

            $subRoutes = $mounted['router']->collectAllRoutes($combinedPrefix, $combinedMiddlewares);
            foreach ($subRoutes as $subRoute) {
                $routes[] = $subRoute;
            }
        }

        return $routes;
    }

    /**
     * Resolves all middlewares matching a given path (globals + patterns).
     *
     * @return list<mixed>
     */
    protected function resolveMiddlewaresForPath(string $path, string $parentPrefix = ''): array
    {
        $resolved = [];

        // 1. Router global middlewares
        foreach ($this->globalMiddlewares as $mw) {
            $resolved[] = $mw;
        }

        // 2. Router pattern-based middlewares
        foreach ($this->patternMiddlewares as $item) {
            $fullPattern = $this->combinePrefixes($parentPrefix, $item['pattern']);
            if ($this->matchPattern($fullPattern, $path)) {
                foreach ($item['middlewares'] as $mw) {
                    $resolved[] = $mw;
                }
            }
        }

        // 3. Recursively for mounted sub-routers if path matches prefix
        foreach ($this->mountedRouters as $mounted) {
            $combinedPrefix = $this->combinePrefixes($parentPrefix, $mounted['prefix']);
            if (str_starts_with($path, $combinedPrefix) || $path === $combinedPrefix || $combinedPrefix === '/') {
                foreach ($mounted['middlewares'] as $mw) {
                    $resolved[] = $mw;
                }

                $subMiddlewares = $mounted['router']->resolveMiddlewaresForPath($path, $combinedPrefix);
                foreach ($subMiddlewares as $smw) {
                    $resolved[] = $smw;
                }
            }
        }

        return $resolved;
    }

    /**
     * Checks if path matches wildcard pattern (e.g. /users/*, /api/v1/**).
     */
    protected function matchPattern(string $pattern, string $path): bool
    {
        $normalizedPattern = '/' . trim($pattern, '/');
        $normalizedPath = '/' . trim($path, '/');

        if ($normalizedPattern === $normalizedPath) {
            return true;
        }

        // Convert patterns:
        // /** -> matches any sub-path
        // /*  -> matches path segment(s)
        $regexPattern = preg_quote($normalizedPattern, '#');
        $regexPattern = str_replace(
            ['/\\*\\*', '/\\*'],
            ['(/.*)?', '(/.*)?'],
            $regexPattern
        );

        return (bool) preg_match('#^' . $regexPattern . '$#u', $normalizedPath);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Executes route handler (Closure, [Controller, 'method'], Controller@method) with Reflection DI.
     *
     * @param array<string, mixed> $params
     */
    protected function executeRouteHandler(Route $route, Request $request, array $params): Response
    {
        $handler = $route->getHandler();

        // 1. Handle Closure or anonymous function
        if ($handler instanceof Closure) {
            $reflector = new ReflectionFunction($handler);
            $arguments = $this->resolveParameters($reflector->getParameters(), $request, $params);
            $result = $handler(...$arguments);
            return $this->normalizeResultToResponse($result);
        }

        // 2. Normalize 'ControllerClass@action' or 'ControllerClass::action' to [class, method]
        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                $handler = explode('@', $handler, 2);
            } elseif (str_contains($handler, '::')) {
                $handler = explode('::', $handler, 2);
            } elseif (class_exists($handler)) {
                // Invokable class
                $handler = [$handler, '__invoke'];
            }
        }

        // 3. Handle [Controller, 'method'] array
        if (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;

            if (is_string($controller)) {
                if (!class_exists($controller)) {
                    throw new ControllerResolutionException(sprintf('Controller class "%s" does not exist.', $controller));
                }
                $controllerInstance = $this->instantiateController($controller, $request);
            } else {
                $controllerInstance = $controller;
            }

            if (!method_exists($controllerInstance, $method)) {
                throw new ControllerResolutionException(sprintf('Method "%s" does not exist in class "%s".', $method, get_class($controllerInstance)));
            }

            $reflector = new ReflectionMethod($controllerInstance, $method);
            $arguments = $this->resolveParameters($reflector->getParameters(), $request, $params);

            $result = $reflector->invokeArgs($controllerInstance, $arguments);
            return $this->normalizeResultToResponse($result);
        }

        if (is_callable($handler)) {
            $result = $handler($request, ...$params);
            return $this->normalizeResultToResponse($result);
        }

        throw new ControllerResolutionException(sprintf('Invalid route handler type: %s.', get_debug_type($handler)));
    }

    /**
     * Instantiates controller with optional constructor DI.
     */
    protected function instantiateController(string $className, Request $request): object
    {
        $classReflector = new ReflectionClass($className);
        $constructor = $classReflector->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return new $className();
        }

        $arguments = $this->resolveParameters($constructor->getParameters(), $request, []);
        return $classReflector->newInstanceArgs($arguments);
    }

    /**
     * Resolves method/function parameters via Reflection API.
     *
     * @param list<ReflectionParameter> $reflectionParams
     * @param array<string, mixed> $routeParams
     * @return list<mixed>
     */
    protected function resolveParameters(array $reflectionParams, Request $request, array $routeParams): array
    {
        $arguments = [];

        foreach ($reflectionParams as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // 1. Inject Request instance
            if ($paramType instanceof ReflectionNamedType && !$paramType->isBuiltin()) {
                $typeName = $paramType->getName();
                if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                    $arguments[] = $request;
                    continue;
                }
            }

            // 2. Match by name from route parameters or wildcard
            if (array_key_exists($paramName, $routeParams)) {
                $val = $routeParams[$paramName];
                $arguments[] = $this->castValueToType($val, $paramType);
                continue;
            }

            // 3. Handle wildcard parameter named 'wildcard', 'path', 'file', or 'slug'
            if (isset($routeParams['wildcard']) && in_array($paramName, ['wildcard', 'path', 'file', 'slug'], true)) {
                $val = $routeParams['wildcard'];
                $arguments[] = $this->castValueToType($val, $paramType);
                continue;
            }

            // 4. Default value from signature
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
                continue;
            }

            // 5. Nullable parameter
            if ($param->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new ControllerResolutionException(sprintf(
                'Cannot resolve required parameter "$%s" in controller action.',
                $paramName
            ));
        }

        return $arguments;
    }

    /**
     * Casts URL string parameter value to the reflection parameter type.
     */
    protected function castValueToType(mixed $value, ?ReflectionType $type): mixed
    {
        if ($type === null || !($type instanceof ReflectionNamedType) || !$type->isBuiltin()) {
            return $value;
        }

        $typeName = $type->getName();

        return match ($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array' => (array) $value,
            default => $value,
        };
    }

    /**
     * Normalizes controller result to a Response object.
     */
    protected function normalizeResultToResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || $result instanceof stdClass || $result instanceof JsonSerializable) {
            return new JsonResponse($result);
        }

        if (is_string($result) || is_numeric($result)) {
            return new Response((string) $result);
        }

        if ($result === null) {
            return new Response('', 204);
        }

        return new Response((string) $result);
    }

    /**
     * Combines URL prefixes ensuring proper slashes.
     */
    protected function combinePrefixes(string $parent, string $child): string
    {
        $p = '/' . trim($parent, '/');
        $c = '/' . trim($child, '/');

        if ($p === '/' && $c === '/') {
            return '/';
        }
        if ($p === '/') {
            return $c;
        }
        if ($c === '/') {
            return $p;
        }

        return $p . $c;
    }
}
