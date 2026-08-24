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
 * File Name: application/builtin/console/Output.php
 * Version: 1.0.0
 * Description: Formatted CLI output with ANSI color support.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\Console;

class Output
{
    /**
     * ANSI color/style codes.
     * 
     * @var array<string, string>
     */
    private const STYLES = [
        'reset'      => "\033[0m",
        'bold'       => "\033[1m",
        'dim'        => "\033[2m",
        'underline'  => "\033[4m",

        // Foreground colors
        'black'      => "\033[30m",
        'red'        => "\033[31m",
        'green'      => "\033[32m",
        'yellow'     => "\033[33m",
        'blue'       => "\033[34m",
        'magenta'    => "\033[35m",
        'cyan'       => "\033[36m",
        'white'      => "\033[37m",
        'gray'       => "\033[90m",

        // Background colors
        'bg_red'     => "\033[41m",
        'bg_green'   => "\033[42m",
        'bg_yellow'  => "\033[43m",
        'bg_blue'    => "\033[44m",
        'bg_magenta' => "\033[45m",
        'bg_cyan'    => "\033[46m",
        'bg_white'   => "\033[47m",
    ];

    /**
     * The output stream (STDOUT).
     * 
     * @var resource
     */
    private $stream;

    /**
     * The error stream (STDERR).
     * 
     * @var resource
     */
    private $errorStream;

    /**
     * Whether ANSI colors are supported.
     * 
     * @var bool
     */
    private bool $ansiSupported;

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Create a new Output instance.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->stream = STDOUT;
        $this->errorStream = STDERR;
        $this->ansiSupported = $this->detectAnsiSupport();
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Write a message to the output (without newline).
     * 
     * @param string $message The message to write.
     * @return self
     */
    public function write(string $message): self
    {
        fwrite($this->stream, $message);
        return $this;
    }

    /**
     * Write a message followed by a newline.
     * 
     * @param string $message The message to write.
     * @return self
     */
    public function writeln(string $message = ''): self
    {
        fwrite($this->stream, $message . PHP_EOL);
        return $this;
    }

    /**
     * Write one or more empty lines.
     * 
     * @param int $count Number of empty lines.
     * @return self
     */
    public function newLine(int $count = 1): self
    {
        fwrite($this->stream, str_repeat(PHP_EOL, $count));
        return $this;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Write a success message (green).
     * 
     * @param string $message The message.
     * @return self
     */
    public function success(string $message): self
    {
        return $this->writeln($this->style(' ✓ ' . $message, 'green', 'bold'));
    }

    /**
     * Write an error message (red).
     * 
     * @param string $message The message.
     * @return self
     */
    public function error(string $message): self
    {
        fwrite($this->errorStream, $this->style(' ✗ ' . $message, 'red', 'bold') . PHP_EOL);
        return $this;
    }

    /**
     * Write a warning message (yellow).
     * 
     * @param string $message The message.
     * @return self
     */
    public function warning(string $message): self
    {
        return $this->writeln($this->style(' ⚠ ' . $message, 'yellow', 'bold'));
    }

    /**
     * Write an info message (cyan).
     * 
     * @param string $message The message.
     * @return self
     */
    public function info(string $message): self
    {
        return $this->writeln($this->style(' ℹ ' . $message, 'cyan'));
    }

    /**
     * Write a comment/muted message (gray).
     * 
     * @param string $message The message.
     * @return self
     */
    public function comment(string $message): self
    {
        return $this->writeln($this->style('   ' . $message, 'gray'));
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Write a section title.
     * 
     * @param string $title The title text.
     * @return self
     */
    public function title(string $title): self
    {
        $this->newLine();
        $this->writeln($this->style(' ' . $title, 'bold', 'cyan'));
        $this->writeln($this->style(' ' . str_repeat('═', mb_strlen($title) + 1), 'cyan'));
        $this->newLine();
        return $this;
    }

    /**
     * Write a section header.
     * 
     * @param string $header The header text.
     * @return self
     */
    public function section(string $header): self
    {
        $this->newLine();
        $this->writeln($this->style(' ' . $header, 'bold', 'yellow'));
        $this->writeln($this->style(' ' . str_repeat('─', mb_strlen($header) + 1), 'yellow'));
        return $this;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Render a table in the terminal.
     * 
     * @param array<int, string> $headers The column headers.
     * @param array<int, array<int, string>> $rows The table rows.
     * @return self
     */
    public function table(array $headers, array $rows): self
    {
        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = mb_strlen($header);
        }
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $cellLength = mb_strlen((string) $cell);
                $widths[$i] = max($widths[$i] ?? 0, $cellLength);
            }
        }

        // Build separator line
        $separatorParts = [];
        foreach ($widths as $width) {
            $separatorParts[] = str_repeat('─', $width + 2);
        }
        $separator = '┼' . implode('┼', $separatorParts) . '┼';
        $topBorder = '┌' . implode('┬', $separatorParts) . '┐';
        $bottomBorder = '└' . implode('┴', $separatorParts) . '┘';

        // Render header
        $this->writeln($this->style($topBorder, 'gray'));
        $headerCells = [];
        foreach ($headers as $i => $header) {
            $headerCells[] = ' ' . $this->style(str_pad($header, $widths[$i]), 'bold', 'cyan') . ' ';
        }
        $this->writeln($this->style('│', 'gray') . implode($this->style('│', 'gray'), $headerCells) . $this->style('│', 'gray'));
        $this->writeln($this->style($separator, 'gray'));

        // Render rows
        foreach ($rows as $row) {
            $rowCells = [];
            foreach ($row as $i => $cell) {
                $rowCells[] = ' ' . str_pad((string) $cell, $widths[$i]) . ' ';
            }
            $this->writeln($this->style('│', 'gray') . implode($this->style('│', 'gray'), $rowCells) . $this->style('│', 'gray'));
        }

        $this->writeln($this->style($bottomBorder, 'gray'));

        return $this;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Display a progress indicator.
     * 
     * @param string $message The progress message.
     * @return self
     */
    public function progress(string $message): self
    {
        return $this->writeln($this->style(' ⟳ ' . $message . '...', 'magenta'));
    }

    /**
     * Display a key-value pair.
     * 
     * @param string $key The label.
     * @param string $value The value.
     * @return self
     */
    public function keyValue(string $key, string $value): self
    {
        return $this->writeln(
            '   ' . $this->style($key . ': ', 'bold') . $value
        );
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Apply ANSI style codes to text.
     * 
     * @param string $text The text to style.
     * @param string ...$styles Style names (e.g., 'bold', 'red', 'bg_blue').
     * @return string The styled text.
     */
    public function style(string $text, string ...$styles): string
    {
        if (!$this->ansiSupported || empty($styles)) {
            return $text;
        }

        $codes = '';
        foreach ($styles as $style) {
            if (isset(self::STYLES[$style])) {
                $codes .= self::STYLES[$style];
            }
        }

        return $codes . $text . self::STYLES['reset'];
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Detect whether the terminal supports ANSI escape codes.
     * 
     * @return bool
     */
    private function detectAnsiSupport(): bool
    {
        // Windows 10+ supports ANSI via ConPTY
        if (DIRECTORY_SEPARATOR === '\\') {
            return (
                getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm'
                || (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support($this->stream))
            );
        }

        return function_exists('posix_isatty') && @posix_isatty($this->stream);
    }

    /**
     * Check if ANSI colors are supported.
     * 
     * @return bool
     */
    public function isAnsiSupported(): bool
    {
        return $this->ansiSupported;
    }

    /**
     * Force enable or disable ANSI support.
     * 
     * @param bool $enabled Whether to enable ANSI.
     * @return self
     */
    public function setAnsiSupported(bool $enabled): self
    {
        $this->ansiSupported = $enabled;
        return $this;
    }
}
