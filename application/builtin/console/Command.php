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
 * File Name: application/builtin/console/Command.php
 * Version: 1.0.0
 * Description: Abstract base class for all CLI commands.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Console;

abstract class Command
{
    /**
     * The command name (e.g., 'composer:install').
     * 
     * @var string
     */
    private string $name = '';

    /**
     * The command description.
     * 
     * @var string
     */
    private string $description = '';

    /**
     * Usage examples.
     * 
     * @var array<int, string>
     */
    private array $usage = [];

    /**
     * Argument definitions: [name => description].
     * 
     * @var array<string, string>
     */
    private array $argumentDefinitions = [];

    /**
     * Option definitions: [name => description].
     * 
     * @var array<string, string>
     */
    private array $optionDefinitions = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Create a new Command instance.
     * 
     * Automatically calls configure() so subclasses can set their metadata.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->configure();
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Configure the command (name, description, arguments, options).
     * 
     * Override this method in subclasses to define command metadata.
     * 
     * @return void
     */
    abstract protected function configure(): void;

    /**
     * Execute the command logic.
     * 
     * @param Input $input The parsed CLI input.
     * @param Output $output The output writer.
     * @return int Exit code (0 = success, non-zero = error).
     */
    abstract protected function execute(Input $input, Output $output): int;

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Run the command (public entry point).
     * 
     * @param Input $input The parsed CLI input.
     * @param Output $output The output writer.
     * @return int Exit code.
     */
    public function run(Input $input, Output $output): int
    {
        return $this->execute($input, $output);
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Set the command name.
     * 
     * @param string $name The command name.
     * @return self
     */
    protected function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the command name.
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the command description.
     * 
     * @param string $description The description.
     * @return self
     */
    protected function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the command description.
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Add a usage example.
     * 
     * @param string $example The usage example string.
     * @return self
     */
    protected function addUsage(string $example): self
    {
        $this->usage[] = $example;
        return $this;
    }

    /**
     * Get all usage examples.
     * 
     * @return array<int, string>
     */
    public function getUsage(): array
    {
        return $this->usage;
    }

    /**
     * Define an expected argument.
     * 
     * @param string $name The argument name.
     * @param string $description The argument description.
     * @return self
     */
    protected function addArgument(string $name, string $description = ''): self
    {
        $this->argumentDefinitions[$name] = $description;
        return $this;
    }

    /**
     * Get argument definitions.
     * 
     * @return array<string, string>
     */
    public function getArgumentDefinitions(): array
    {
        return $this->argumentDefinitions;
    }

    /**
     * Define an expected option.
     * 
     * @param string $name The option name.
     * @param string $description The option description.
     * @return self
     */
    protected function addOption(string $name, string $description = ''): self
    {
        $this->optionDefinitions[$name] = $description;
        return $this;
    }

    /**
     * Get option definitions.
     * 
     * @return array<string, string>
     */
    public function getOptionDefinitions(): array
    {
        return $this->optionDefinitions;
    }
}
