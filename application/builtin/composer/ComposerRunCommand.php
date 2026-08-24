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
 * File Name: application/builtin/composer/ComposerRunCommand.php
 * Version: 1.0.0
 * Description: CLI command to run arbitrary Composer commands.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Composer;

use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;

class ComposerRunCommand extends Command
{
    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Configure the command.
     * 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('composer:run')
             ->setDescription('Run an arbitrary Composer command.')
             ->addArgument('command', 'The Composer command to run (e.g., "show --tree").')
             ->addUsage('php webcycles composer:run show')
             ->addUsage('php webcycles composer:run "show --tree"')
             ->addUsage('php webcycles composer:run "dump-autoload --optimize"');
    }

    /**
     * Execute the command.
     * 
     * @param Input $input The CLI input.
     * @param Output $output The CLI output.
     * @return int Exit code.
     */
    protected function execute(Input $input, Output $output): int
    {
        $arguments = $input->getArguments();

        if (empty($arguments)) {
            $output->error('Missing required argument: <command>');
            $output->comment('Usage: php webcycles composer:run <command>');
            return 1;
        }

        $composer = new Composer();

        if (!$composer->isInstalled()) {
            $output->error('Composer is not installed. Run "php webcycles composer:install" first.');
            return 1;
        }

        // Combine all remaining arguments as the Composer command
        $composerCommand = implode(' ', $arguments);

        $output->progress("Running: composer {$composerCommand}");
        $output->newLine();

        $result = $composer->run($composerCommand);

        if ($result['output']) {
            $output->writeln($result['output']);
        }

        if ($result['exitCode'] !== 0) {
            $output->newLine();
            $output->error("Command failed with exit code {$result['exitCode']}");
            return $result['exitCode'];
        }

        return 0;
    }
}
