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
 * File Name: application/builtin/composer/Commands/ComposerRequireCommand.php
 * Version: 1.0.0
 * Description: CLI command to require (add) a Composer package.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Composer\Commands;

use WebCycles\Foundations\Composer\Composer;
use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;

class ComposerRequireCommand extends Command
{
    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Configure the command.
     * 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('composer:require')
             ->setDescription('Add a package to the project dependencies.')
             ->addArgument('package', 'The package name (e.g., monolog/monolog or monolog/monolog:^2.0).')
             ->addOption('dev', 'Add as a development dependency.')
             ->addUsage('php webcycles composer:require monolog/monolog')
             ->addUsage('php webcycles composer:require monolog/monolog:^2.0')
             ->addUsage('php webcycles composer:require phpunit/phpunit --dev');
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
        $package = $input->getArgument(0);

        if ($package === null) {
            $output->error('Missing required argument: <package>');
            $output->comment('Usage: php webcycles composer:require <package>');
            return 1;
        }

        $composer = new Composer();

        if (!$composer->isInstalled()) {
            $output->error('Composer is not installed. Run "php webcycles composer:install" first.');
            return 1;
        }

        // Parse package:version format
        $version = null;
        if (str_contains($package, ':')) {
            [$package, $version] = explode(':', $package, 2);
        }

        $extraArgs = [];
        if ($input->hasOption('dev')) {
            $extraArgs[] = '--dev';
        }

        $displayName = $version !== null ? "{$package}:{$version}" : $package;
        $output->progress("Requiring {$displayName}");

        $result = $composer->require($package, $version, $extraArgs);

        if ($result['output']) {
            $output->writeln($result['output']);
        }

        if ($result['exitCode'] !== 0) {
            $output->error("Failed to require {$displayName}");
            return $result['exitCode'];
        }

        $output->success("Package {$displayName} added successfully.");
        return 0;
    }
}
