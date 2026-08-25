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
 * File Name: application/builtin/http/Pipeline.php
 * Version: 1.0.0
 * Description: Pipeline execution mechanism (Onion Architecture) for HTTP Request and Middleware.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

use Closure;
use InvalidArgumentException;
use JsonSerializable;
use WebCycles\Foundations\HTTP\Middleware\MiddlewareInterface;
use stdClass;

/**
 * Pipeline execution mechanism (Onion Architecture) for Request and Middleware.
 */
class Pipeline
{
    protected ?Request $passable = null;

    /**
     * @var list<mixed>
     */
    protected array $pipes = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Sets the request object being passed through the pipeline.
     */
    public function send(Request $request): static
    {
        $this->passable = $request;
        return $this;
    }

    /**
     * Sets the array of pipes (middlewares).
     *
     * @param list<mixed> $pipes
     */
    public function through(array $pipes): static
    {
        $this->pipes = array_values($pipes);
        return $this;
    }

    /**
     * Runs the pipeline with a final destination callback.
     *
     * @param Closure(Request): mixed $destination
     * @return Response
     */
    public function then(Closure $destination): Response
    {
        if ($this->passable === null) {
            throw new InvalidArgumentException('No Request object in Pipeline. Call send($request) first.');
        }

        $pipeline = array_reduce(
            array_reverse($this->pipes),
            fn(Closure $next, mixed $pipe): Closure => fn(Request $request): Response => $this->runPipe($pipe, $request, $next),
            fn(Request $request): Response => $this->toResponse($destination($request))
        );

        return $pipeline($this->passable);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Executes a single middleware pipe.
     *
     * @param mixed $pipe MiddlewareInterface instance, class name, or callable
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    protected function runPipe(mixed $pipe, Request $request, Closure $next): Response
    {
        if ($pipe instanceof MiddlewareInterface) {
            return $pipe->handle($request, $next);
        }

        if (is_string($pipe)) {
            if (!class_exists($pipe)) {
                throw new InvalidArgumentException(sprintf('Middleware class "%s" does not exist.', $pipe));
            }

            $instance = new $pipe();
            if ($instance instanceof MiddlewareInterface) {
                return $instance->handle($request, $next);
            }

            if (is_callable($instance)) {
                $result = $instance($request, $next);
                return $this->toResponse($result);
            }

            throw new InvalidArgumentException(sprintf('Class "%s" must implement %s or be invokable (__invoke).', $pipe, MiddlewareInterface::class));
        }

        if (is_callable($pipe)) {
            $result = $pipe($request, $next);
            return $this->toResponse($result);
        }

        throw new InvalidArgumentException(sprintf('Invalid middleware type: %s.', get_debug_type($pipe)));
    }

    /**
     * Converts any result value to a Response object.
     */
    protected function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || $result instanceof stdClass || $result instanceof JsonSerializable) {
            return new JsonResponse($result);
        }

        if (is_string($result) || is_numeric($result) || is_null($result)) {
            return new Response((string) ($result ?? ''));
        }

        return new Response((string) $result);
    }
}
