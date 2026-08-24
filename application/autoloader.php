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
 * Description: 
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations;

class Autoloader
{
    /**
     * The namespace prefix for built-in system components.
     * 
     * @var string
     */
    private const SYSTEM_NAMESPACE_PREFIX = 'WebCycles\\Foundations\\';

    /**
     * Path to the cached classmap file.
     * 
     * @var string
     */
    private string $cacheFile;

    /**
     * Path to the builtin components directory.
     * 
     * @var string
     */
    private string $builtinPath;

    /**
     * The classmap array: [FQCN => absolute file path].
     * 
     * @var array<string, string>
     */
    private array $classMap = [];

    /**
     * Whether the classmap was loaded from cache.
     * 
     * @var bool
     */
    private bool $loadedFromCache = false;

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Create a new Autoloader instance.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->builtinPath = WEBCYCLES_APPLICATION_BUILTIN_PATH;
        $this->cacheFile   = WEBCYCLES_STORAGE_CACHE_PATH
            . DIRECTORY_SEPARATOR . 'webcycles'
            . DIRECTORY_SEPARATOR . 'autoloader_classmap.generated.php';
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Register the autoloader with the SPL autoload stack.
     * 
     * Loads the classmap from cache if available,
     * otherwise scans the builtin directory and generates the cache.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->loadClassMap();

        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Unregister the autoloader from the SPL autoload stack.
     * 
     * @return void
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Attempt to load a class by its fully qualified name.
     * 
     * Only handles classes under the WebCycles\Foundations\ namespace.
     * User components are NOT handled by this autoloader.
     * 
     * @param string $className The fully qualified class name.
     * @return void
     */
    public function loadClass(string $className): void
    {
        // Only handle system (builtin) classes
        if (!str_starts_with($className, self::SYSTEM_NAMESPACE_PREFIX)) {
            return;
        }

        if (isset($this->classMap[$className])) {
            $filePath = $this->classMap[$className];

            if (file_exists($filePath)) {
                require_once $filePath;
            }
        }
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Load the classmap — from cache if valid, otherwise by scanning.
     * 
     * @return void
     */
    private function loadClassMap(): void
    {
        if ($this->loadFromCache()) {
            $this->loadedFromCache = true;
            return;
        }

        $this->classMap = $this->scanBuiltinDirectory();
        $this->writeCache();
    }

    /**
     * Try to load the classmap from the cached file.
     * 
     * @return bool True if cache was loaded successfully.
     */
    private function loadFromCache(): bool
    {
        if (!file_exists($this->cacheFile)) {
            return false;
        }

        $cached = require $this->cacheFile;

        if (!is_array($cached)) {
            return false;
        }

        $this->classMap = $cached;
        return true;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Recursively scan the builtin directory for PHP class files
     * and build a classmap based on namespace conventions.
     * 
     * Convention:
     *   application/builtin/{component}/{Class}.php
     *   → WebCycles\Foundations\{Component}\{Class}
     * 
     * @return array<string, string> The generated classmap.
     */
    private function scanBuiltinDirectory(): array
    {
        $map = [];

        if (!is_dir($this->builtinPath)) {
            return $map;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->builtinPath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getRealPath();

            // Extract the namespace and class from the file
            $classInfo = $this->extractClassInfo($filePath);

            if ($classInfo !== null) {
                $map[$classInfo] = $filePath;
            }
        }

        return $map;
    }

    /**
     * Extract the fully qualified class name from a PHP file.
     * 
     * Parses namespace and class/interface/trait/enum declarations
     * using token analysis for accuracy.
     * 
     * @param string $filePath Absolute path to the PHP file.
     * @return string|null The FQCN, or null if not found.
     */
    private function extractClassInfo(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        $tokens    = token_get_all($contents);
        $namespace = '';
        $className = '';
        $count     = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            // Extract namespace
            if ($token[0] === T_NAMESPACE) {
                $namespaceParts = [];
                $i++;

                while ($i < $count) {
                    $next = $tokens[$i];

                    if (is_array($next) && in_array($next[0], [T_NAME_QUALIFIED, T_STRING], true)) {
                        $namespaceParts[] = $next[1];
                    } elseif ($next === ';' || $next === '{') {
                        break;
                    }

                    $i++;
                }

                $namespace = implode('\\', $namespaceParts);
            }

            // Extract class/interface/trait/enum name
            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                // Skip anonymous classes
                if ($token[0] === T_CLASS) {
                    // Look back for 'new' keyword (anonymous class)
                    for ($j = $i - 1; $j >= 0; $j--) {
                        if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                            continue;
                        }
                        if (is_array($tokens[$j]) && $tokens[$j][0] === T_NEW) {
                            continue 2; // Skip this class token
                        }
                        break;
                    }
                }

                $i++;
                while ($i < $count) {
                    $next = $tokens[$i];

                    if (is_array($next) && $next[0] === T_STRING) {
                        $className = $next[1];
                        break;
                    }

                    $i++;
                }

                break; // Only take the first class/interface/trait/enum
            }
        }

        if ($className === '') {
            return null;
        }

        // Only register classes under the system namespace prefix
        $fqcn = $namespace !== '' ? $namespace . '\\' . $className : $className;

        if (!str_starts_with($fqcn, self::SYSTEM_NAMESPACE_PREFIX)) {
            return null;
        }

        return $fqcn;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Write the classmap array to the cache file.
     * 
     * @return void
     */
    private function writeCache(): void
    {
        $cacheDir = dirname($this->cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $content  = "<?php\n\n";
        $content .= "/*\n";
        $content .= " *     ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ    \n";
        $content .= " *  ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ \n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆ   ÆÆÆ   ÆÆÆ   ÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆ                       \n";
        $content .= " * ÆÆÆÆÆÆÆÆÆ                        \n";
        $content .= " * ÆÆÆÆÆÆÆÆÆ                        \n";
        $content .= " * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ\n";
        $content .= " * ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ\n";
        $content .= " *  ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ \n";
        $content .= " *     ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ   \n";
        $content .= " * \n";
        $content .= " *             WebCycles\n";
        $content .= " * \n";
        $content .= " * Description: Auto-generated system autoloader classmap.\n";
        $content .= " * \n";
        $content .= " * auto-generated file — do not edit manually.\n";
        $content .= " * Generated at: " . date('Y-m-d H:i:s') . "\n";
        $content .= " */\n\n";
        $content .= "/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */\n\n";
        $content .= "return " . var_export($this->classMap, true) . ";\n";

        file_put_contents($this->cacheFile, $content, LOCK_EX);

        // Invalidate OPcache for this file if available
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->cacheFile, true);
        }
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Force a rebuild of the classmap cache.
     * 
     * Useful after adding or removing system components.
     * 
     * @return void
     */
    public function rebuild(): void
    {
        $this->classMap = $this->scanBuiltinDirectory();
        $this->writeCache();
    }

    /**
     * Clear the cached classmap file.
     * 
     * @return bool True if the file was deleted or didn't exist.
     */
    public function clearCache(): bool
    {
        if (file_exists($this->cacheFile)) {
            return unlink($this->cacheFile);
        }

        return true;
    }

    /**
     * Get the current classmap.
     * 
     * @return array<string, string>
     */
    public function getClassMap(): array
    {
        return $this->classMap;
    }

    /**
     * Check whether the classmap was loaded from cache.
     * 
     * @return bool
     */
    public function isLoadedFromCache(): bool
    {
        return $this->loadedFromCache;
    }
}