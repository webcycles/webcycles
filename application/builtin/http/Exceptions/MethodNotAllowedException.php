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
 * File Name: application/builtin/http/Exceptions/MethodNotAllowedException.php
 * Version: 1.0.0
 * Description: 405 Method Not Allowed HTTP exception with Allow header generation.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP\Exceptions;

use Throwable;

/**
 * 405 Method Not Allowed HTTP exception thrown when a route exists for the path but not for the requested HTTP method.
 * Automatically populates the 'Allow' header with supported HTTP methods.
 */
class MethodNotAllowedException extends HttpException
{
    /**
     * @var list<string>
     */
    protected array $allowedMethods;

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(
        array $allowedMethods = [],
        string $currentMethod = '',
        string $message = '',
        array $headers = [],
        ?Throwable $previous = null
    ) {
        $this->allowedMethods = array_values(array_unique(array_map('strtoupper', $allowedMethods)));

        $headers['Allow'] = implode(', ', $this->allowedMethods);

        if ($message === '') {
            $message = sprintf(
                'Method %s is not allowed for this resource. Allowed methods: %s.',
                strtoupper($currentMethod),
                implode(', ', $this->allowedMethods)
            );
        }

        parent::__construct(405, $message, $headers, $previous);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns the list of allowed HTTP methods for the route.
     *
     * @return list<string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
