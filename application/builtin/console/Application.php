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
 * File Name: application/builtin/console/Application.php
 * Version: 1.0.0
 * Description: Console application — registers and dispatches CLI commands.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Console;

class Application
{
    /**
     * The application name.
     * 
     * @var string
     */
    private string $name;

    /**
     * The application version.
     * 
     * @var string
     */
    private string $version;

    /**
     * Registered commands: [name => Command].
     * 
     * @var array<string, Command>
     */
    private array $commands = [];

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Create a new Application instance.
     * 
     * @param string $name The application name.
     * @param string $version The application version.
     * @return void
     */
    public function __construct(string $name = 'WebCycles', string $version = '1.0.0')
    {
        $this->name = $name;
        $this->version = $version;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Register a command with the application.
     * 
     * @param Command $command The command instance.
     * @return self
     */
    public function add(Command $command): self
    {
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    /**
     * Get a registered command by name.
     * 
     * @param string $name The command name.
     * @return Command|null
     */
    public function get(string $name): ?Command
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * Check if a command is registered.
     * 
     * @param string $name The command name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * Get all registered commands.
     * 
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Run the console application with CLI input and output.
     * 
     * Parses CLI arguments, finds the matching command, and executes it.
     * If no command is specified, displays the command list.
     * 
     * @param Input|null $input Optional input instance.
     * @param Output|null $output Optional output instance.
     * @return int Exit code (0 = success).
     */
    public function run(?Input $input = null, ?Output $output = null): int
    {
        $input  = $input ?? new Input();
        $output = $output ?? new Output();

        $commandName = $input->getCommandName();

        // No command — show the list
        if ($commandName === null) {
            return $this->showCommandList($output);
        }

        // Help flag
        if ($commandName === 'help') {
            if ($input->hasArgument(0)) {
                return $this->showCommandHelp($input->getArgument(0), $output);
            }
            return $this->showCommandList($output);
        }

        // --help or -h with a command name: show help for that command
        if ($input->hasOption('help') || $input->hasOption('h')) {
            if ($this->has($commandName)) {
                return $this->showCommandHelp($commandName, $output);
            }
            return $this->showCommandList($output);
        }

        // About command
        if ($commandName === 'about' || $input->hasOption('about')) {
            return $this->showAbout($output);
        }

        // Version flag
        if ($commandName === 'version' || $input->hasOption('version') || $input->hasOption('V')) {
            return $this->showVersion($output);
        }

        // Find and execute the command
        $command = $this->get($commandName);

        if ($command === null) {
            // Check if commandName is a namespace/group (e.g. "composer")
            if ($this->hasCommandGroup($commandName)) {
                return $this->showCommandList($output, $commandName);
            }

            $output->error("Unknown command: \"{$commandName}\"");
            $output->newLine();
            $this->suggestCommand($commandName, $output);
            return 1;
        }

        try {
            return $command->run($input, $output);
        } catch (\Throwable $e) {
            $output->newLine();
            $output->error($e->getMessage());
            $output->comment($e->getFile() . ':' . $e->getLine());
            $output->newLine();
            return 1;
        }
    }

    /**
     * Programmatically execute a command by name and parameters.
     * 
     * Example:
     *   $app->execute('composer:install');
     *   $app->execute('composer:require', ['monolog/monolog', '--dev']);
     * 
     * @param string $commandName The name of the command to execute.
     * @param array<int, string> $parameters Optional arguments and options (e.g. ['package/name', '--dev']).
     * @param Output|null $output Optional custom Output instance.
     * @return int The exit code.
     */
    public function execute(string $commandName, array $parameters = [], ?Output $output = null): int
    {
        $argv = array_merge(['webcycles', $commandName], $parameters);
        $input = new Input($argv);
        $output = $output ?? new Output();

        $command = $this->get($commandName);

        if ($command === null) {
            $output->error("Unknown command: \"{$commandName}\"");
            return 1;
        }

        try {
            return $command->run($input, $output);
        } catch (\Throwable $e) {
            $output->newLine();
            $output->error($e->getMessage());
            $output->comment($e->getFile() . ':' . $e->getLine());
            $output->newLine();
            return 1;
        }
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Check if a command group / namespace exists.
     * 
     * @param string $group The group name (e.g. 'composer')
     * @return bool
     */
    private function hasCommandGroup(string $group): bool
    {
        $prefix = rtrim($group, ':') . ':';
        foreach (array_keys($this->commands) as $name) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Display the application banner and available commands.
     * 
     * @param Output $output The output writer.
     * @param string|null $filterGroup Optional group prefix filter (e.g. 'composer').
     * @return int Exit code (always 0).
     */
    private function showCommandList(Output $output, ?string $filterGroup = null): int
    {
        $this->showBanner($output);

        // Core / system commands
        $coreCommands = [
            'about'   => 'Display information about WebCycles application.',
            'help'    => 'Display help for a command.',
            'version' => 'Display WebCycles application version.',
        ];

        // Group commands by prefix
        $groups = [];
        $filterPrefix = $filterGroup !== null ? rtrim($filterGroup, ':') : null;

        // If no filter or filtering by app/core group, include core commands
        if ($filterPrefix === null || $filterPrefix === 'webcycles' || $filterPrefix === 'app') {
            $groups['_default'] = $coreCommands;
        }

        foreach ($this->commands as $name => $command) {
            $parts = explode(':', $name, 2);
            $group = count($parts) > 1 ? $parts[0] : '_default';

            if ($filterPrefix !== null && $group !== $filterPrefix) {
                continue;
            }

            $groups[$group][$name] = $command instanceof Command ? $command->getDescription() : (string) $command;
        }

        ksort($groups);

        // Find max command name length for alignment
        $maxLen = 0;
        foreach ($groups as $commands) {
            foreach (array_keys($commands) as $name) {
                $maxLen = max($maxLen, mb_strlen($name));
            }
        }

        if (empty($groups)) {
            $output->comment("No commands found for group \"{$filterGroup}\".");
            $output->newLine();
            return 0;
        }

        if ($filterPrefix !== null) {
            $output->writeln($output->style(" Available commands in [{$filterPrefix}]:", 'bold'));
        } else {
            $output->writeln($output->style(' Available commands:', 'bold'));
        }
        $output->newLine();

        // Print _default / core commands first if present
        if (isset($groups['_default'])) {
            $defaultCommands = $groups['_default'];
            ksort($defaultCommands);

            foreach ($defaultCommands as $name => $desc) {
                $paddedName = str_pad($name, $maxLen + 2);
                $output->writeln(
                    '    ' . $output->style($paddedName, 'green') . $desc
                );
            }
            unset($groups['_default']);
        }

        foreach ($groups as $group => $commands) {
            if ($filterPrefix === null) {
                $output->writeln($output->style('  ' . $group, 'yellow'));
            }

            ksort($commands);

            foreach ($commands as $name => $desc) {
                $paddedName = str_pad($name, $maxLen + 2);
                $output->writeln(
                    '    ' . $output->style($paddedName, 'green') . $desc
                );
            }
        }

        $output->newLine();
        $output->comment('Use "php webcycles <command> --help" for more information about a command.');
        $output->newLine();

        return 0;
    }

    /**
     * Display help for a specific command.
     * 
     * @param string $commandName The command name.
     * @param Output $output The output writer.
     * @return int Exit code.
     */
    private function showCommandHelp(string $commandName, Output $output): int
    {
        $command = $this->get($commandName);

        if ($command === null) {
            $output->error("Unknown command: \"{$commandName}\"");
            return 1;
        }

        $output->newLine();
        $output->writeln($output->style(' ' . $command->getName(), 'bold', 'green'));
        $output->writeln('   ' . $command->getDescription());

        $usage = $command->getUsage();
        if (!empty($usage)) {
            $output->newLine();
            $output->writeln($output->style(' Usage:', 'bold'));
            foreach ($usage as $example) {
                $output->writeln('   ' . $output->style($example, 'cyan'));
            }
        }

        $args = $command->getArgumentDefinitions();
        if (!empty($args)) {
            $output->newLine();
            $output->writeln($output->style(' Arguments:', 'bold'));
            $maxArgLen = max(array_map('mb_strlen', array_keys($args)));
            foreach ($args as $argName => $argDesc) {
                $paddedArg = str_pad($argName, $maxArgLen + 2);
                $output->writeln('   ' . $output->style($paddedArg, 'green') . $argDesc);
            }
        }

        $opts = $command->getOptionDefinitions();
        if (!empty($opts)) {
            $output->newLine();
            $output->writeln($output->style(' Options:', 'bold'));
            $maxOptLen = max(array_map('mb_strlen', array_keys($opts)));
            foreach ($opts as $optName => $optDesc) {
                $paddedOpt = str_pad('--' . $optName, $maxOptLen + 4);
                $output->writeln('   ' . $output->style($paddedOpt, 'green') . $optDesc);
            }
        }

        $output->newLine();
        return 0;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Display the application banner with ASCII logo.
     * 
     * @param Output $output The output writer.
     * @return void
     */
    private function showBanner(Output $output): void
    {
        $logo = [
            "    ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ    ",
            " ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ ",
            "ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆ   ÆÆÆ   ÆÆÆ   ÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆÆ             ÆÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆÆ                       ",
            "ÆÆÆÆÆÆÆÆÆ                        ",
            "ÆÆÆÆÆÆÆÆÆ                        ",
            "ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆ               ÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆ                 ÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ",
            "ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ",
            " ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ ",
            "    ÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆÆ   "
        ];

        $output->newLine();
        foreach ($logo as $line) {
            $output->writeln($output->style($line, 'cyan'));
        }
        $output->newLine();
        $output->writeln($output->style('            ' . $this->name, 'bold', 'white') . ' ' . $output->style('v' . $this->version, 'gray'));
        $output->newLine();
    }

    /**
     * Display the application version.
     * 
     * @param Output $output The output writer.
     * @return int Exit code (always 0).
     */
    private function showVersion(Output $output): int
    {
        $output->writeln($this->name . ' ' . $output->style('v' . $this->version, 'green'));
        return 0;
    }

    /**
     * Display detailed information about the WebCycles environment.
     * 
     * @param Output $output The output writer.
     * @return int Exit code (always 0).
     */
    private function showAbout(Output $output): int
    {
        $this->showBanner($output);

        $output->section('Environment & System Info');
        $output->keyValue('WebCycles Version', $this->version);
        $output->keyValue('PHP Version', PHP_VERSION);
        $output->keyValue('PHP Binary', PHP_BINARY);
        $output->keyValue('OS', PHP_OS_FAMILY . ' (' . PHP_OS . ')');
        if (defined('WEBCYCLES_PATH')) {
            $output->keyValue('Root Path', WEBCYCLES_PATH);
        }
        if (defined('WEBCYCLES_STORAGE_PATH')) {
            $output->keyValue('Storage Path', WEBCYCLES_STORAGE_PATH);
        }

        $composer = new \WebCycles\Foundations\Composer\Composer();
        $composerVersion = $composer->getVersion() ?? 'Not installed (run: php webcycles composer:install)';
        $output->keyValue('Composer', $composerVersion);

        $output->newLine();
        return 0;
    }

    /**
     * Suggest similar commands when a command is not found.
     * 
     * @param string $name The attempted command name.
     * @param Output $output The output writer.
     * @return void
     */
    private function suggestCommand(string $name, Output $output): void
    {
        $suggestions = [];

        foreach (array_keys($this->commands) as $commandName) {
            $distance = levenshtein($name, $commandName);
            if ($distance <= 3) {
                $suggestions[$commandName] = $distance;
            }
        }

        if (!empty($suggestions)) {
            asort($suggestions);
            $output->writeln(' Did you mean one of these?');
            foreach (array_keys($suggestions) as $suggestion) {
                $output->writeln('   ' . $output->style($suggestion, 'green'));
            }
            $output->newLine();
        }
    }
}
