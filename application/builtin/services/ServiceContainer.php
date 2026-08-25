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
 * File Name: application/builtin/services/ServiceContainer.php
 * Version: 1.0.0
 * Description: Primary dependency injection container (DIC).
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Services;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionNamedType;
use InvalidArgumentException;

use WebCycles\Foundations\Services\Binding;
use WebCycles\Foundations\Services\LazyProxy;
use WebCycles\Foundations\Services\ServiceProvider;
use WebCycles\Foundations\Services\ContextualBuilder;

use WebCycles\Foundations\Services\Exceptions\NotFoundException;
use WebCycles\Foundations\Services\Exceptions\ContainerException;
use WebCycles\Foundations\Services\Exceptions\BindingResolutionException;
use WebCycles\Foundations\Services\Exceptions\CircularDependencyException;

use WebCycles\Foundations\Services\Interfaces\ContainerInterface;

/**
 * Primary dependency injection container (DIC).
 *
 * Handles object lifecycle management (transient, singleton, scoped),
 * automatic dependency resolution (Autowiring) via reflection,
 * contextual bindings, decoration (extenders), and lazy loading.
 *
 * @since 1.0.0
 */
class ServiceContainer implements ContainerInterface
{
    /**
     * Global static container instance (Singleton).
     *
     * @since 1.0.0
     * @var ContainerInterface|null $instance
     */
    private static ?ContainerInterface $instance = null;

    /**
     * Get global container instance.
     *
     * @since 1.0.0
     * @return self|null
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * Set global container instance.
     *
     * @since 1.0.0
     * @param ContainerInterface|null $container Container instance
     * @return void
     */
    public static function setInstance(?ContainerInterface $container): void
    {
        self::$instance = $container;
    }

    /**
     * Registered binding definitions.
     *
     * @since 1.0.0
     * @var array<string, Binding> $bindings
     */
    private array $bindings = [];

    /**
     * Cached singleton instances.
     *
     * @since 1.0.0
     * @var array<string, mixed> $instances
     */
    private array $instances = [];

    /**
     * Request scoped instances.
     *
     * @since 1.0.0
     * @var array<string, mixed> $scopedInstances
     */
    private array $scopedInstances = [];

    /**
     * Registered service aliases (e.g. 'db' => Connection::class).
     *
     * @since 1.0.0
     * @var array<string, string> $aliases
     */
    private array $aliases = [];

    /**
     * Tags assigned to services for grouped resolution.
     *
     * @since 1.0.0
     * @var array<string, string[]> $tags
     */
    private array $tags = [];

    /**
     * Contextual bindings.
     *
     * @since 1.0.0
     * @var array<string, array<string, mixed>> $contextual
     */
    private array $contextual = [];

    /**
     * Service extenders / decorators.
     *
     * @since 1.0.0
     * @var array<string, Closure[]> $extenders
     */
    private array $extenders = [];

    /**
     * Callbacks invoked during service resolution.
     *
     * @since 1.0.0
     * @var Closure[] $resolvingCallbacks
     */
    private array $resolvingCallbacks = [];

    /**
     * Callbacks invoked after complete service resolution.
     *
     * @since 1.0.0
     * @var Closure[] $resolvedCallbacks
     */
    private array $resolvedCallbacks = [];

    /**
     * Cache for reflection objects (performance optimization).
     *
     * @since 1.0.0
     * @var array $reflectionCache
     */
    private array $reflectionCache = [];

    /**
     * Stack of currently building classes to detect circular dependencies.
     *
     * @since 1.0.0
     * @var array $buildStack
     */
    private array $buildStack = [];

    /**
     * Currently resolving class context (for contextual bindings).
     *
     * @since 1.0.0
     * @var string|null $currentContext
     */
    private ?string $currentContext = null;

    /**
     * Whether a request scope is currently active.
     *
     * @since 1.0.0
     * @var bool $scopeActive
     */
    private bool $scopeActive = false;

    /**
     * Register a transient binding (new instance on each make/get call).
     *
     * @since 1.0.0
     * @param string $abstract Abstract type name (class or interface)
     * @param mixed $factory Factory creating the instance (Closure, class name, or value)
     * @param array $tags Tags assigned to the binding
     * @return static
     */
    public function bind(
        string $abstract,
        mixed $factory = null,
        array $tags = [],
    ): static {
        $this->dropStaleInstances($abstract);
        $factory ??= $abstract;

        $this->bindings[$abstract] = new Binding(
            abstract: $abstract,
            factory: $factory,
            singleton: false,
            tags: $tags,
        );

        foreach ($tags as $tag) {
            $this->tags[$tag][] = $abstract;
        }

        return $this;
    }

    /**
     * Register a singleton binding (single instance across application lifecycle).
     *
     * @since 1.0.0
     * @param string $abstract Abstract type name
     * @param mixed $factory Factory creating the instance
     * @param array $tags Tags assigned to the binding
     * @return static
     */
    public function singleton(
        string $abstract,
        mixed $factory = null,
        array $tags = [],
    ): static {
        $this->dropStaleInstances($abstract);
        $factory ??= $abstract;

        if (is_object($factory) && !($factory instanceof Closure)) {
            return $this->instance($abstract, $factory);
        }

        $this->bindings[$abstract] = new Binding(
            abstract: $abstract,
            factory: $factory,
            singleton: true,
            tags: $tags,
        );

        foreach ($tags as $tag) {
            $this->tags[$tag][] = $abstract;
        }

        return $this;
    }

    /**
     * Register a lazy singleton — instantiated on first access.
     *
     * @since 1.0.0
     * @param string $abstract Abstract type name
     * @param mixed $factory Factory creating the instance
     * @return static
     */
    public function lazy(string $abstract, mixed $factory = null): static
    {
        $this->dropStaleInstances($abstract);
        $factory ??= $abstract;

        $this->bindings[$abstract] = new Binding(
            abstract: $abstract,
            factory: $factory,
            singleton: true,
            lazy: true,
        );

        return $this;
    }

    /**
     * Register an existing instance as a shared singleton.
     *
     * @since 1.0.0
     * @param string $abstract Abstract type name
     * @param mixed $instance Resolved service instance
     * @return static
     */
    public function instance(string $abstract, mixed $instance): static
    {
        $this->removeAlias($abstract);
        $this->instances[$abstract] = $instance;
        return $this;
    }

    /**
     * Alias an abstract type to another name.
     *
     * @since 1.0.0
     * @param string $abstract Original abstract type
     * @param string $alias New alias name
     * @return static
     * @throws InvalidArgumentException When aliasing to itself
     */
    public function alias(string $abstract, string $alias): static
    {
        if ($abstract === $alias) {
            throw new InvalidArgumentException(
                "[$abstract] is aliased to itself.",
            );
        }
        $this->aliases[$alias] = $abstract;
        return $this;
    }

    /**
     * Register a scoped binding.
     *
     * Behaves like a singleton within a given scope (e.g. single HTTP request),
     * and gets cleared once the scope completes.
     *
     * @since 1.0.0
     * @param string $abstract Abstract type name
     * @param mixed $factory Factory creating the instance
     * @return static
     */
    public function scoped(string $abstract, mixed $factory = null): static
    {
        $this->bind($abstract, $factory);
        $this->bindings[$abstract] = new Binding(
            abstract: $abstract,
            factory: $this->bindings[$abstract]->factory,
            singleton: false, // manually managed in scopedInstances
        );
        return $this;
    }

    /**
     * Get a contextual binding builder for a concrete class.
     *
     * @since 1.0.0
     * @param string $concrete Target concrete class name
     * @return ContextualBuilder
     */
    public function when(string $concrete): ContextualBuilder
    {
        return new ContextualBuilder($this, $concrete);
    }

    /**
     * Add a defined contextual binding.
     *
     * @since 1.0.0
     * @internal Internal method for ContextualBuilder
     * @param string $concrete
     * @param string $needs
     * @param mixed $give
     * @return void
     */
    public function addContextualBinding(
        string $concrete,
        string $needs,
        mixed $give,
    ): void {
        $this->contextual[$concrete][$needs] = $give;
    }

    /**
     * Decorate or modify an existing service after it is resolved.
     *
     * @since 1.0.0
     * @param string $abstract Abstract service type
     * @param Closure $closure Extender callback
     * @return static
     */
    public function extend(string $abstract, Closure $closure): static
    {
        $abstract = $this->getAlias($abstract);
        $this->extenders[$abstract][] = $closure;

        // If singleton already exists, apply immediately
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure(
                $this->instances[$abstract],
                $this,
            );
        }

        return $this;
    }

    /**
     * Register and boot a service provider.
     *
     * @since 1.0.0
     * @param ServiceProvider $provider Service provider instance
     * @return static
     */
    public function register(ServiceProvider $provider): static
    {
        $provider->register($this);
        if (method_exists($provider, "boot")) {
            $provider->boot($this);
        }
        return $this;
    }

    /**
     * Resolve and return the given type from the container.
     *
     * @since 1.0.0
     * @param string $abstract Class or interface name
     * @param array $parameters Extra parameters passed to constructor
     * @return mixed Resolved service instance
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Finds an entry of the container by its identifier (PSR-11 ContainerInterface).
     *
     * @since 1.0.0
     * @param string $id Identifier of the entry to look for
     * @return mixed
     * @throws NotFoundException If no entry was found for the identifier
     */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (ContainerException $e) {
            throw new NotFoundException("No entry found for [$id].", 0, $e);
        }
    }

    /**
     * Returns true if the container can return an entry for the given identifier (PSR-11).
     *
     * @since 1.0.0
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * Check if a given type is bound in the container.
     *
     * @since 1.0.0
     * @param string $abstract Abstract type name
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            isset($this->aliases[$abstract]);
    }

    /**
     * Call the given Closure / callable and inject its dependencies.
     *
     * @since 1.0.0
     * @param mixed $callable Callable (Closure, string, array)
     * @param array $parameters Parameter overrides
     * @return mixed Call result
     */
    public function call(mixed $callable, array $parameters = []): mixed
    {
        if (is_string($callable) && str_contains($callable, "@")) {
            [$class, $method] = explode("@", $callable, 2);
            $callable = [$this->make($class), $method];
        }

        if (is_array($callable) && is_string($callable[0])) {
            $callable[0] = $this->make($callable[0]);
        }

        $rf = is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction(Closure::fromCallable($callable));

        $deps = $this->resolveParameters($rf->getParameters(), $parameters);
        if ($rf instanceof ReflectionMethod) {
            return $rf->invokeArgs(
                is_array($callable) ? $callable[0] : null,
                $deps,
            );
        }
        return $rf->invokeArgs($deps);
    }

    /**
     * Run a callback within an isolated scope.
     *
     * @since 1.0.0
     * @param Closure $callback Function executed within scope
     * @return mixed Callback result
     */
    public function runInScope(Closure $callback): mixed
    {
        $this->scopeActive = true;
        try {
            return $callback($this);
        } finally {
            $this->scopedInstances = [];
            $this->scopeActive = false;
        }
    }

    /**
     * Get all registered services associated with the given tag.
     *
     * @since 1.0.0
     * @param string $tag Tag name
     * @return array Array of resolved service instances
     */
    public function tagged(string $tag): array
    {
        $results = [];
        foreach ($this->tags[$tag] ?? [] as $abstract) {
            $results[] = $this->make($abstract);
        }
        return $results;
    }

    /**
     * Assign tags to the specified service abstractions.
     *
     * @since 1.0.0
     * @param string|array $abstracts Service names
     * @param string|array $tags Tags
     * @return static
     */
    public function tag(string|array $abstracts, string|array $tags): static
    {
        foreach ((array) $tags as $tag) {
            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
        return $this;
    }

    /**
     * Register a callback to be run before resolving any service.
     *
     * @since 1.0.0
     * @param Closure $callback
     * @return static
     */
    public function resolving(Closure $callback): static
    {
        $this->resolvingCallbacks[] = $callback;
        return $this;
    }

    /**
     * Register a callback to be run after fully resolving a service.
     *
     * @since 1.0.0
     * @param Closure $callback
     * @return static
     */
    public function afterResolving(Closure $callback): static
    {
        $this->resolvedCallbacks[] = $callback;
        return $this;
    }

    /**
     * Get all registered binding definitions.
     *
     * @since 1.0.0
     * @return array<string, Binding>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get the keys of all instantiated singleton instances.
     *
     * @since 1.0.0
     * @return array
     */
    public function getInstances(): array
    {
        return array_keys($this->instances);
    }

    /**
     * Determine if a given service is shared (singleton/shared).
     *
     * @since 1.0.0
     * @param string $abstract Service type name
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]) &&
                $this->bindings[$abstract]->singleton);
    }

    /**
     * Remove a cached singleton instance from the container.
     *
     * @since 1.0.0
     * @param string $abstract Service type name
     * @return static
     */
    public function forgetInstance(string $abstract): static
    {
        unset($this->instances[$abstract]);
        return $this;
    }

    /**
     * Flush all cached singleton instances from the container.
     *
     * @since 1.0.0
     * @return static
     */
    public function forgetInstances(): static
    {
        $this->instances = [];
        return $this;
    }

    /**
     * Resolve a service, handling Lazy Loading Proxy if configured.
     */
    private function resolve(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        // Pre-resolved instance (singleton already instantiated)
        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        // Lazy proxy
        $binding = $this->bindings[$abstract] ?? null;
        if ($binding?->lazy && empty($parameters)) {
            return new LazyProxy(
                fn() => $this->resolveNow($abstract, $parameters, $binding),
            );
        }

        return $this->resolveNow($abstract, $parameters, $binding);
    }

    /**
     * Perform actual dependency injection and object building.
     */
    private function resolveNow(
        string $abstract,
        array $parameters,
        ?Binding $binding,
    ): mixed {
        // Detect circular dependencies
        if (in_array($abstract, $this->buildStack, true)) {
            throw new CircularDependencyException(
                "Circular dependency detected: " .
                    implode(" → ", $this->buildStack) .
                    " → $abstract",
            );
        }

        $this->buildStack[] = $abstract;
        $previousContext = $this->currentContext;
        $this->currentContext = $abstract;

        try {
            foreach ($this->resolvingCallbacks as $cb) {
                $cb($abstract, $this);
            }

            $object = $this->build($abstract, $parameters, $binding);

            // Apply extenders
            foreach ($this->extenders[$abstract] ?? [] as $extender) {
                $object = $extender($object, $this);
            }

            // Singleton cache
            if ($binding?->singleton && empty($parameters)) {
                $this->instances[$abstract] = $object;
            }

            foreach ($this->resolvedCallbacks as $cb) {
                $cb($object, $this);
            }

            return $object;
        } finally {
            array_pop($this->buildStack);
            $this->currentContext = $previousContext;
        }
    }

    /**
     * Build an instance using factory or autowiring.
     */
    private function build(
        string $abstract,
        array $parameters,
        ?Binding $binding,
    ): mixed {
        $factory = $binding?->factory ?? $abstract;

        // Closure factory
        if ($factory instanceof Closure) {
            return $factory($this, $parameters);
        }

        // Rebound abstract
        if (
            is_string($factory) &&
            $factory !== $abstract &&
            $this->bound($factory)
        ) {
            return $this->make($factory, $parameters);
        }

        $concrete = is_string($factory) ? $factory : $abstract;

        // Autowire via Reflection
        return $this->autowire($concrete, $parameters);
    }

    /**
     * Autowire a concrete class using constructor reflection.
     */
    private function autowire(string $class, array $parameters = []): object
    {
        if (!class_exists($class)) {
            throw new BindingResolutionException(
                "Class [$class] does not exist.",
            );
        }

        $reflector = $this->getReflector($class);

        if (!$reflector->isInstantiable()) {
            throw new BindingResolutionException(
                "[$class] is not instantiable.",
            );
        }

        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return new $class();
        }

        $deps = $this->resolveParameters(
            $constructor->getParameters(),
            $parameters,
        );
        return $reflector->newInstanceArgs($deps);
    }

    /**
     * Resolve reflection parameters for constructor / callable.
     */
    private function resolveParameters(
        array $rfParams,
        array $overrides = [],
    ): array {
        $results = [];

        foreach ($rfParams as $param) {
            $name = $param->getName();

            // Manual parameter override
            if (array_key_exists($name, $overrides)) {
                $results[] = $overrides[$name];
                continue;
            }

            $type = $param->getType();

            // Contextual binding
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $needs = $type->getName();
                $context = $this->currentContext;

                if ($context && isset($this->contextual[$context][$needs])) {
                    $give = $this->contextual[$context][$needs];
                    $results[] =
                        $give instanceof Closure
                        ? $give($this)
                        : $this->make($give);
                    continue;
                }

                try {
                    $results[] = $this->make($needs);
                    continue;
                } catch (CircularDependencyException $e) {
                    throw $e;
                } catch (ContainerException) {
                    // Fallthrough to default parameter value
                }
            }

            if ($param->isDefaultValueAvailable()) {
                $results[] = $param->getDefaultValue();
                continue;
            }

            if ($param->allowsNull()) {
                $results[] = null;
                continue;
            }

            // Try autowiring non-builtin types if binding exists
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $results[] = $this->make($type->getName());
                continue;
            }

            throw new BindingResolutionException(
                "Cannot resolve parameter [\$$name] in [{$param->getDeclaringClass()?->getName()}].",
            );
        }

        return $results;
    }

    /**
     * Get and cache ReflectionClass instance for performance optimization.
     */
    private function getReflector(string $class): ReflectionClass
    {
        return $this->reflectionCache[$class] ??= new ReflectionClass($class);
    }

    /**
     * Recursively resolve alias to original abstract type name.
     */
    private function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Remove stale alias.
     */
    private function removeAlias(string $abstract): void
    {
        $this->aliases = array_filter(
            $this->aliases,
            fn($target) => $target !== $abstract,
        );
    }

    /**
     * Drop stale singleton instance.
     */
    private function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Return list of all registered definitions, instances and aliases in the container.
     */
    public function getRegisteredServices(): array
    {
        $keys = array_unique(array_merge(
            array_keys($this->bindings),
            array_keys($this->instances),
            array_keys($this->aliases)
        ));
        sort($keys);
        return $keys;
    }

    /**
     * Get registered aliases in the container.
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }
}
