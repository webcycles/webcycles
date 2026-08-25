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
 * File Name: application/builtin/services/ContextualBuilder.php
 * Version: 1.0.0
 * Description: Contextual binding builder for the service container.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Services;

use WebCycles\Foundations\Services\ServiceContainer;

/**
 * Contextual binding builder for the service container.
 *
 * Allows defining conditional dependency injection depending on which
 * class (concrete) is requesting a given interface/class (needs).
 *
 * @since 1.0.0
 */
class ContextualBuilder
{
    /**
     * Required abstraction (e.g. interface).
     *
     * @since 1.0.0
     * @var string $needs
     */
    private string $needs;

    /**
     * Initialize a new contextual builder instance.
     *
     * @since 1.0.0
     * @param ServiceContainer $container Service container
     * @param string $concrete Target class name receiving the contextual dependency
     */
    public function __construct(
        private readonly ServiceContainer $container,
        private readonly string $concrete,
    ) {}

    /**
     * Specify the dependency abstraction/interface to be injected contextually.
     *
     * @since 1.0.0
     * @param string $abstract Required abstraction (e.g. interface)
     * @return static
     */
    public function needs(string $abstract): static
    {
        $this->needs = $abstract;
        return $this;
    }

    /**
     * Specify the concrete implementation to inject for the declared dependency.
     *
     * @since 1.0.0
     * @param mixed $implementation Implementation (class name, Closure, or value)
     * @return void
     */
    public function give(mixed $implementation): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->needs,
            $implementation,
        );
    }
}
