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
 * File Name: application/builtin/http/UploadedFile.php
 * Version: 1.0.0
 * Description: HTTP Uploaded file representation ($_FILES) with validation and move handlers.
 * Copyright: WebCycles (c) 2026
 * License: MIT License
 * Authors: 
 *  - Bartłomiej 'Machina' Walczak <machina@duck.com>
 */

declare(strict_types=1);

/* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

namespace WebCycles\Foundations\HTTP;

use RuntimeException;

/**
 * Representation of an uploaded file (from $_FILES) with validation and file operations.
 */
class UploadedFile
{
    protected string $originalName;
    protected string $mimeType;
    protected string $tmpName;
    protected int $error;
    protected int $size;

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    public function __construct(
        string $originalName,
        string $mimeType,
        string $tmpName,
        int $error = UPLOAD_ERR_OK,
        int $size = 0
    ) {
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->tmpName = $tmpName;
        $this->error = $error;
        $this->size = $size;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Checks whether the file was uploaded successfully without errors.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpName);
    }

    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    public function getClientMimeType(): string
    {
        return $this->mimeType;
    }

    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    public function getPathname(): string
    {
        return $this->tmpName;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Returns file contents as string.
     */
    public function getContent(): string
    {
        $content = @file_get_contents($this->tmpName);
        if ($content === false) {
            throw new RuntimeException(sprintf('Cannot read file contents: "%s".', $this->tmpName));
        }
        return $content;
    }

    /**
     * Returns readable upload error message.
     */
    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK => 'The file was uploaded successfully.',
            UPLOAD_ERR_INI_SIZE => 'The file exceeds upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The file exceeds MAX_FILE_SIZE directive specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'An unknown error occurred while uploading the file.',
        };
    }

    /**
     * Moves uploaded file to the target directory.
     *
     * @param string $targetDirectory Target folder
     * @param ?string $name New file name (optional)
     * @return string Full path to the saved file
     */
    public function moveTo(string $targetDirectory, ?string $name = null): string
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf('Cannot move file due to upload error: %s', $this->getErrorMessage()));
        }

        $targetDirectory = rtrim($targetDirectory, '/\\');
        if (!is_dir($targetDirectory)) {
            if (!mkdir($targetDirectory, 0777, true) && !is_dir($targetDirectory)) {
                throw new RuntimeException(sprintf('Cannot create target directory "%s".', $targetDirectory));
            }
        }

        if (!is_writable($targetDirectory)) {
            throw new RuntimeException(sprintf('Target directory "%s" is not writable.', $targetDirectory));
        }

        $fileName = $name !== null && $name !== '' ? $name : $this->getClientOriginalName();
        // Remove potentially dangerous characters from filename
        $fileName = basename($fileName);
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (is_uploaded_file($this->tmpName)) {
            if (!move_uploaded_file($this->tmpName, $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file to "%s".', $targetPath));
            }
        } else {
            // Support CLI and test environments
            if (!rename($this->tmpName, $targetPath)) {
                throw new RuntimeException(sprintf('Error copying file to "%s".', $targetPath));
            }
        }

        @chmod($targetPath, 0666 & ~umask());
        return $targetPath;
    }

    /* =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

    /**
     * Recursively normalizes $_FILES array into uniform UploadedFile instances.
     *
     * @param array<string, mixed> $files
     * @return array<string, mixed>
     */
    public static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof self) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);
            }
        }

        return $normalized;
    }

    /**
     * Processes single or nested $_FILES file structure.
     */
    private static function createUploadedFileFromSpec(array $value): array|self|null
    {
        if (is_array($value['tmp_name'])) {
            $files = [];
            foreach (array_keys($value['tmp_name']) as $k) {
                $spec = [
                    'name' => $value['name'][$k] ?? '',
                    'type' => $value['type'][$k] ?? '',
                    'tmp_name' => $value['tmp_name'][$k] ?? '',
                    'error' => $value['error'][$k] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $value['size'][$k] ?? 0,
                ];
                $files[$k] = self::createUploadedFileFromSpec($spec);
            }
            return $files;
        }

        if (empty($value['tmp_name']) && ($value['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return new self(
            $value['name'] ?? '',
            $value['type'] ?? 'application/octet-stream',
            $value['tmp_name'] ?? '',
            (int) ($value['error'] ?? UPLOAD_ERR_OK),
            (int) ($value['size'] ?? 0)
        );
    }
}
