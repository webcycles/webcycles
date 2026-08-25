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
 * File Name: application/builtin/composer/Commands/ComposerUpdateCommand.php
 * Version: 1.0.0
 * Description: CLI command to update Composer dependencies.
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

class ComposerUpdateCommand extends Command
{
    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Configure the command.
     * 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('composer:update')
             ->setDescription('Update project dependencies.')
             ->addArgument('packages', 'Optional specific packages to update (space-separated).')
             ->addUsage('php webcycles composer:update')
             ->addUsage('php webcycles composer:update monolog/monolog');
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
        $composer = new Composer();

        if (!$composer->isInstalled()) {
            $output->error('Composer is not installed. Run "php webcycles composer:install" first.');
            return 1;
        }

        $packages = $input->getArguments();

        if (empty($packages)) {
            $output->progress('Updating all dependencies');
        } else {
            $output->progress('Updating: ' . implode(', ', $packages));
        }

        $result = $composer->update($packages);

        if ($result['output']) {
            $output->writeln($result['output']);
        }

        if ($result['exitCode'] !== 0) {
            $output->error('Failed to update dependencies.');
            return $result['exitCode'];
        }

        $output->success('Dependencies updated successfully.');
        return 0;
    }
}
