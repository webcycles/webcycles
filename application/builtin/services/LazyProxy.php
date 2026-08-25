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
 * File Name: application/builtin/services/LazyProxy.php
 * Version: 1.0.0
 * Description: Lazy loading proxy object for services.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Services;

use Closure;

/**
 * Lazy loading proxy object for services.
 *
 * Represents a virtual proxy that defers target object instantiation until
 * any method is called, a property is accessed, or resolve() is explicitly invoked.
 *
 * @since 1.0.0
 */
class LazyProxy
{
    /**
     * Flag indicating whether the target object has already been instantiated.
     *
     * @since 1.0.0
     * @var bool $resolved
     */
    private bool $resolved = false;

    /**
     * Holds the created instance of the target object.
     *
     * @since 1.0.0
     * @var mixed $instance
     */
    private mixed $instance = null;

    /**
     * Initialize a new LazyProxy instance.
     *
     * @since 1.0.0
     * @param Closure $resolver Dependency resolution callback returning target object instance
     */
    public function __construct(private readonly Closure $resolver) {}

    /**
     * Resolve and instantiate the target object (only once).
     *
     * @since 1.0.0
     * @return mixed Target object instance
     */
    public function resolve(): mixed
    {
        if (!$this->resolved) {
            $this->instance = ($this->resolver)();
            $this->resolved = true;
        }
        return $this->instance;
    }

    /**
     * Forward method calls to the target object.
     *
     * @since 1.0.0
     * @param string $name Method name
     * @param array $args Method arguments
     * @return mixed Method call result
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->resolve()->$name(...$args);
    }

    /**
     * Get property from the target object.
     *
     * @since 1.0.0
     * @param string $name Property name
     * @return mixed Property value
     */
    public function __get(string $name): mixed
    {
        return $this->resolve()->$name;
    }

    /**
     * Set property on the target object.
     *
     * @since 1.0.0
     * @param string $name Property name
     * @param mixed $value New property value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->resolve()->$name = $value;
    }
}
