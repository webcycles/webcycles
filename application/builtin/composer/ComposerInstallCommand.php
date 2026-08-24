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
 * File Name: application/builtin/composer/ComposerInstallCommand.php
 * Version: 1.0.0
 * Description: CLI command to download and install Composer.
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

class ComposerInstallCommand extends Command
{
    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Configure the command.
     * 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('composer:install')
             ->setDescription('Download and install Composer to the runtime directory.')
             ->addOption('force', 'Force reinstall even if Composer is already installed.')
             ->addUsage('php webcycles composer:install')
             ->addUsage('php webcycles composer:install --force');
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

        $output->title('Composer Installation');

        // Check if already installed
        if ($composer->isInstalled() && !$input->hasOption('force')) {
            $version = $composer->getVersion() ?? 'unknown';
            $output->info('Composer is already installed.');
            $output->keyValue('Version', $version);
            $output->keyValue('Location', $composer->getPharPath());
            $output->newLine();
            $output->comment('Use --force to reinstall.');
            $output->newLine();
            return 0;
        }

        if ($composer->isInstalled()) {
            $output->warning('Reinstalling Composer...');
        }

        // Download and install
        $output->progress('Downloading Composer');
        $result = $composer->download();

        if (!$result['success']) {
            $output->error($result['message']);
            return 1;
        }

        $output->success($result['message']);
        $output->newLine();

        // Show version
        $version = $composer->getVersion();
        if ($version !== null) {
            $output->keyValue('Version', $version);
        }
        $output->keyValue('Location', $composer->getPharPath());
        $output->newLine();

        return 0;
    }
}
