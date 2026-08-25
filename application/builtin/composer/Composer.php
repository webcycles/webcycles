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
 * File Name: application/builtin/composer/Composer.php
 * Version: 1.0.0
 * Description: Composer manager — downloads, installs and proxies all Composer operations.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Composer;

class Composer
{
    /**
     * URL to download the Composer installer.
     * 
     * @var string
     */
    private const COMPOSER_INSTALLER_URL = 'https://getcomposer.org/installer';

    /**
     * URL to fetch the expected installer signature.
     * 
     * @var string
     */
    private const COMPOSER_SIGNATURE_URL = 'https://composer.github.io/installer.sig';

    /**
     * Path to the Composer runtime directory.
     * 
     * @var string
     */
    private string $composerDir;

    /**
     * Path to the composer.phar file.
     * 
     * @var string
     */
    private string $pharPath;

    /**
     * Working directory for Composer operations.
     * 
     * @var string
     */
    private string $workingDir;

    /**
     * Path to the PHP binary.
     * 
     * @var string
     */
    private string $phpBinary;

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Create a new Composer instance.
     * 
     * @param string|null $workingDir The working directory. Defaults to WEBCYCLES_PATH.
     * @return void
     */
    public function __construct(?string $workingDir = null)
    {
        $this->composerDir = WEBCYCLES_STORAGE_RUNTIME_PATH . DIRECTORY_SEPARATOR . 'composer';
        $this->pharPath    = $this->composerDir . DIRECTORY_SEPARATOR . 'composer.phar';
        $this->workingDir  = $workingDir ?? WEBCYCLES_PATH;
        $this->phpBinary   = PHP_BINARY;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Check if composer.phar is installed.
     * 
     * @return bool
     */
    public function isInstalled(): bool
    {
        return file_exists($this->pharPath);
    }

    /**
     * Get the path to the composer.phar file.
     * 
     * @return string
     */
    public function getPharPath(): string
    {
        return $this->pharPath;
    }

    /**
     * Get the Composer runtime directory.
     * 
     * @return string
     */
    public function getComposerDir(): string
    {
        return $this->composerDir;
    }

    /**
     * Get the Composer version string.
     * 
     * @return string|null The version string, or null if not installed.
     */
    public function getVersion(): ?string
    {
        if (!$this->isInstalled()) {
            return null;
        }

        $result = $this->execute(['--version', '--no-ansi']);

        if ($result['exitCode'] !== 0) {
            return null;
        }

        // Parse "Composer version X.Y.Z ..."
        if (preg_match('/Composer version (\S+)/', $result['output'], $matches)) {
            return $matches[1];
        }

        return trim($result['output']);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Download and install composer.phar to the runtime directory.
     * 
     * Verifies the installer signature for security.
     * 
     * @return array{success: bool, message: string} The result of the installation.
     */
    public function download(): array
    {
        // Ensure the runtime directory exists
        if (!is_dir($this->composerDir)) {
            if (!mkdir($this->composerDir, 0755, true)) {
                return [
                    'success' => false,
                    'message' => 'Failed to create directory: ' . $this->composerDir,
                ];
            }
        }

        // Download the expected signature
        $expectedSignature = @file_get_contents(self::COMPOSER_SIGNATURE_URL);

        if ($expectedSignature === false) {
            return [
                'success' => false,
                'message' => 'Failed to download Composer installer signature. Check your internet connection.',
            ];
        }

        $expectedSignature = trim($expectedSignature);

        // Download the installer
        $installerPath = $this->composerDir . DIRECTORY_SEPARATOR . 'composer-setup.php';
        $installer = @file_get_contents(self::COMPOSER_INSTALLER_URL);

        if ($installer === false) {
            return [
                'success' => false,
                'message' => 'Failed to download Composer installer. Check your internet connection.',
            ];
        }

        file_put_contents($installerPath, $installer);

        // Verify the installer signature
        $actualSignature = hash_file('sha384', $installerPath);

        if ($actualSignature !== $expectedSignature) {
            @unlink($installerPath);
            return [
                'success' => false,
                'message' => 'Composer installer signature verification failed! '
                    . "Expected: {$expectedSignature}, Got: {$actualSignature}",
            ];
        }

        // Run the installer
        $argvBackup = $GLOBALS['argv'] ?? ($_SERVER['argv'] ?? []);
        $argcBackup = $GLOBALS['argc'] ?? ($_SERVER['argc'] ?? 0);

        $customArgv = [
            'composer-setup.php',
            '--install-dir',
            $this->composerDir,
            '--filename',
            'composer.phar',
            '--quiet',
        ];
        $customArgc = count($customArgv);

        $GLOBALS['argv'] = $_SERVER['argv'] = $customArgv;
        $GLOBALS['argc'] = $_SERVER['argc'] = $customArgc;

        ob_start();
        $exitCode = 0;
        try {
            $result = (static function (string $installerPath) {
                global $argv, $argc;
                return include $installerPath;
            })($installerPath);

            if ($result === false) {
                $exitCode = 1;
            }
        } catch (\Throwable $e) {
            $exitCode = 1;
            echo $e->getMessage();
        } finally {
            $output = ob_get_clean();
            $GLOBALS['argv'] = $_SERVER['argv'] = $argvBackup;
            $GLOBALS['argc'] = $_SERVER['argc'] = $argcBackup;
        }

        // Clean up the installer
        @unlink($installerPath);

        if ($exitCode !== 0 || !$this->isInstalled()) {
            return [
                'success' => false,
                'message' => 'Composer installer failed: ' . ($output ?: 'composer.phar was not created at ' . $this->pharPath),
            ];
        }

        return [
            'success' => true,
            'message' => 'Composer installed successfully to: ' . $this->pharPath,
        ];
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Run `composer install`.
     * 
     * @param array<int, string> $extraArgs Additional arguments.
     * @return array{exitCode: int, output: string}
     */
    public function install(array $extraArgs = []): array
    {
        return $this->execute(array_merge(['install'], $extraArgs));
    }

    /**
     * Run `composer update`.
     * 
     * @param array<int, string> $packages Specific packages to update (empty = all).
     * @param array<int, string> $extraArgs Additional arguments.
     * @return array{exitCode: int, output: string}
     */
    public function update(array $packages = [], array $extraArgs = []): array
    {
        return $this->execute(array_merge(['update'], $packages, $extraArgs));
    }

    /**
     * Run `composer require`.
     * 
     * @param string $package The package name (e.g., 'monolog/monolog').
     * @param string|null $version The version constraint (e.g., '^2.0').
     * @param array<int, string> $extraArgs Additional arguments.
     * @return array{exitCode: int, output: string}
     */
    public function require(string $package, ?string $version = null, array $extraArgs = []): array
    {
        $args = ['require', $version !== null ? $package . ':' . $version : $package];
        return $this->execute(array_merge($args, $extraArgs));
    }

    /**
     * Run `composer remove`.
     * 
     * @param string $package The package name.
     * @param array<int, string> $extraArgs Additional arguments.
     * @return array{exitCode: int, output: string}
     */
    public function remove(string $package, array $extraArgs = []): array
    {
        return $this->execute(array_merge(['remove', $package], $extraArgs));
    }

    /**
     * Run `composer dump-autoload`.
     * 
     * @param array<int, string> $extraArgs Additional arguments.
     * @return array{exitCode: int, output: string}
     */
    public function dumpAutoload(array $extraArgs = []): array
    {
        return $this->execute(array_merge(['dump-autoload'], $extraArgs));
    }

    /**
     * Run `composer show`.
     * 
     * @param string|null $package Specific package name, or null for all.
     * @param array<int, string> $extraArgs Additional arguments.
     * @return array{exitCode: int, output: string}
     */
    public function show(?string $package = null, array $extraArgs = []): array
    {
        $args = ['show'];
        if ($package !== null) {
            $args[] = $package;
        }
        return $this->execute(array_merge($args, $extraArgs));
    }

    /**
     * Run `composer outdated`.
     * 
     * @param array<int, string> $extraArgs Additional arguments.
     * @return array{exitCode: int, output: string}
     */
    public function outdated(array $extraArgs = []): array
    {
        return $this->execute(array_merge(['outdated'], $extraArgs));
    }

    /**
     * Run `composer self-update`.
     * 
     * @return array{exitCode: int, output: string}
     */
    public function selfUpdate(): array
    {
        return $this->execute(['self-update']);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Run any arbitrary Composer command.
     * 
     * @param string $command The raw command string (e.g., 'show --tree').
     * @return array{exitCode: int, output: string}
     */
    public function run(string $command): array
    {
        $args = preg_split('/\s+/', trim($command));
        return $this->execute($args !== false ? $args : [$command]);
    }

    /**
     * Prepare a path or string argument for CLI execution on the current OS.
     * 
     * On Windows, CLI subshells (cmd.exe/exec) expect paths in the active ANSI codepage
     * rather than UTF-8 if they contain non-ASCII characters.
     * 
     * @param string $argument
     * @return string
     */
    private function prepareCliArgument(string $argument): string
    {
        if (PHP_OS_FAMILY === 'Windows' && function_exists('sapi_windows_cp_get') && function_exists('sapi_windows_cp_conv')) {
            $ansiCp = sapi_windows_cp_get('ansi');
            if (is_int($ansiCp)) {
                $converted = @sapi_windows_cp_conv(65001, $ansiCp, $argument);
                if (is_string($converted) && $converted !== '') {
                    return $converted;
                }
            }
        }

        return $argument;
    }

    /**
     * Execute a Composer command with the given arguments.
     * 
     * @param array<int, string> $args The command arguments.
     * @return array{exitCode: int, output: string}
     * @throws \RuntimeException If Composer is not installed.
     */
    public function execute(array $args): array
    {
        if (!$this->isInstalled()) {
            throw new \RuntimeException(
                'Composer is not installed. Run "php webcycles composer:install" first.'
            );
        }

        $command = sprintf(
            '%s %s --working-dir=%s --no-interaction %s 2>&1',
            escapeshellarg($this->prepareCliArgument($this->phpBinary)),
            escapeshellarg($this->prepareCliArgument($this->pharPath)),
            escapeshellarg($this->prepareCliArgument($this->workingDir)),
            implode(' ', array_map(fn(string $arg) => escapeshellarg($this->prepareCliArgument($arg)), $args))
        );

        // Set COMPOSER_HOME to keep Composer config isolated
        $composerHome = $this->composerDir . DIRECTORY_SEPARATOR . 'home';
        if (!is_dir($composerHome)) {
            mkdir($composerHome, 0755, true);
        }
        putenv('COMPOSER_HOME=' . $composerHome);

        $outputLines = [];
        $exitCode = 0;
        exec($command, $outputLines, $exitCode);

        return [
            'exitCode' => $exitCode,
            'output'   => implode(PHP_EOL, $outputLines),
        ];
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Set the working directory for Composer operations.
     * 
     * @param string $path The working directory path.
     * @return self
     */
    public function setWorkingDir(string $path): self
    {
        $this->workingDir = $path;
        return $this;
    }

    /**
     * Get the current working directory.
     * 
     * @return string
     */
    public function getWorkingDir(): string
    {
        return $this->workingDir;
    }

    /**
     * Set the PHP binary path.
     * 
     * @param string $path The PHP binary path.
     * @return self
     */
    public function setPhpBinary(string $path): self
    {
        $this->phpBinary = $path;
        return $this;
    }
}