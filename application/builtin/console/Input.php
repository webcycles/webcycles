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
 * File Name: application/builtin/console/Input.php
 * Version: 1.0.0
 * Description: CLI input parser for arguments and options.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Console;

class Input
{
    /**
     * The raw arguments from CLI.
     * 
     * @var array<int, string>
     */
    private array $rawArgv;

    /**
     * The command name (first argument).
     * 
     * @var string|null
     */
    private ?string $commandName = null;

    /**
     * Parsed positional arguments (after the command name).
     * 
     * @var array<int, string>
     */
    private array $arguments = [];

    /**
     * Parsed options (--key=value, --flag, -f).
     * 
     * @var array<string, string|bool>
     */
    private array $options = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Create a new Input instance.
     * 
     * @param array<int, string>|null $argv The CLI arguments. Defaults to global $argv.
     * @return void
     */
    public function __construct(?array $argv = null)
    {
        $this->rawArgv = $argv ?? ($_SERVER['argv'] ?? []);
        $this->parse();
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Parse the raw CLI arguments into command name, arguments, and options.
     * 
     * @return void
     */
    private function parse(): void
    {
        $args = $this->rawArgv;

        // Remove the script name (first element)
        array_shift($args);

        // First non-option argument is the command name
        $positional = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $this->parseLongOption($arg);
            } elseif (str_starts_with($arg, '-') && strlen($arg) > 1) {
                $this->parseShortOption($arg);
            } else {
                $positional[] = $arg;
            }
        }

        // First positional is the command name
        if (!empty($positional)) {
            $this->commandName = array_shift($positional);
            $this->arguments = $positional;
        }
    }

    /**
     * Parse a long option (--key=value or --flag).
     * 
     * @param string $arg The raw argument string.
     * @return void
     */
    private function parseLongOption(string $arg): void
    {
        $option = substr($arg, 2);

        if (str_contains($option, '=')) {
            [$key, $value] = explode('=', $option, 2);
            $this->options[$key] = $value;
        } else {
            $this->options[$option] = true;
        }
    }

    /**
     * Parse a short option (-f or -f=value).
     * 
     * @param string $arg The raw argument string.
     * @return void
     */
    private function parseShortOption(string $arg): void
    {
        $option = substr($arg, 1);

        if (str_contains($option, '=')) {
            [$key, $value] = explode('=', $option, 2);
            $this->options[$key] = $value;
        } else {
            // Each character is a separate flag: -abc = -a -b -c
            for ($i = 0; $i < strlen($option); $i++) {
                $this->options[$option[$i]] = true;
            }
        }
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Get the command name.
     * 
     * @return string|null
     */
    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    /**
     * Get a positional argument by index (0-based, after command name).
     * 
     * @param int $index The argument index.
     * @return string|null The argument value or null.
     */
    public function getArgument(int $index): ?string
    {
        return $this->arguments[$index] ?? null;
    }

    /**
     * Get all positional arguments.
     * 
     * @return array<int, string>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Check if a positional argument exists at the given index.
     * 
     * @param int $index The argument index.
     * @return bool
     */
    public function hasArgument(int $index): bool
    {
        return isset($this->arguments[$index]);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Get an option value.
     * 
     * @param string $name The option name (without -- or -).
     * @param string|bool|null $default Default value if option not set.
     * @return string|bool|null
     */
    public function getOption(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Check if an option is set.
     * 
     * @param string $name The option name.
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Get all parsed options.
     * 
     * @return array<string, string|bool>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Get the raw argv array.
     * 
     * @return array<int, string>
     */
    public function getRawArgv(): array
    {
        return $this->rawArgv;
    }
}
