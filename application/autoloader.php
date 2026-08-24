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
 * File Name: application/autoloader.php
 * Version: 1.0.0
 * Description: TODO
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

declare(strict_types=1);

namespace WebCycles\Foundations;

class Autoloader
{
    private bool $debug;
    private bool $strict;

    /** @var array<string, string> prefix => base_directory */
    private array $prefixes = [];

    public function __construct(bool $debug = false, bool $strict = false)
    {
        $this->debug = $debug;
        $this->strict = $strict;
    }

    public function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->prefixes[$prefix] = $baseDir;
    }

    public function register(bool $prepend = true): void
    {
        // 1. Rejestracja wbudowanych komponentów
        if (is_dir(WEBCYCLES_APPLICATION_BUILTIN_PATH)) {
            $builtin = new \DirectoryIterator(WEBCYCLES_APPLICATION_BUILTIN_PATH);

            foreach ($builtin as $component) {
                if ($component->isDot() || !$component->isDir()) {
                    continue;
                }

                $componentName = $component->getBasename();
                $componentPath = $component->getPathname();

                // Mapuje np. "WebCycles\Http\" -> "/path/to/builtin/Http/"
                $this->addNamespace('WebCycles\\Foundations\\' . $componentName, $componentPath);
            }
        }

        // 2. Rejestracja callbacku w silniku PHP
        spl_autoload_register([$this, 'load'], true, $prepend);
    }

    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'load']);
    }

    public function load(string $class): bool
    {
        foreach ($this->prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);

            // Sprawdź czy klasa zaczyna się od danego prefixu
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            // Odetnij prefix (np. z "WebCycles\Http\Request" zostaje "Request")
            $relativeClass = substr($class, $len);

            // Zbuduj ścieżkę: /path/to/builtin/Http/Request.php
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (is_file($file)) {
                require $file;
                return true;
            }
        }

        return false;
    }
}