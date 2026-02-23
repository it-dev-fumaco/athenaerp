<?php

namespace App\Http\Helpers;

use Illuminate\Support\Str;

/**
 * Safe path and filename helpers to prevent path traversal and unsafe file handling.
 */
class SafePath
{
    /**
     * Ensure path has no ".." and no NUL bytes (path traversal / null-byte injection).
     */
    public static function pathContainsTraversal(string $path): bool
    {
        if (str_contains($path, "\0")) {
            return true;
        }
        $normalized = str_replace('\\', '/', $path);

        return str_contains($normalized, '..');
    }

    /**
     * Validate that a path stays under a given prefix (no traversal, prefix enforced).
     */
    public static function pathUnderPrefix(string $path, string $allowedPrefix): bool
    {
        if (self::pathContainsTraversal($path)) {
            return false;
        }
        $path = str_replace('\\', '/', $path);
        $allowedPrefix = rtrim(str_replace('\\', '/', $allowedPrefix), '/');

        return $path === $allowedPrefix || Str::startsWith($path, $allowedPrefix.'/');
    }

    /**
     * Sanitize a path segment (filename or single folder name) for storage paths.
     */
    public static function sanitizeSegment(string $segment): string
    {
        $segment = trim($segment);
        $segment = str_replace(['/', '\\', "\0", '..'], '', $segment);

        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $segment) ?? '';
    }

    /**
     * Resolve path against a root and ensure result stays under root.
     */
    public static function resolveUnderRoot(string $root, string $path): ?string
    {
        if (self::pathContainsTraversal($path)) {
            return null;
        }
        $root = rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/');
        $path = str_replace('\\', '/', $path);
        $resolved = $root.'/'.ltrim($path, '/');
        $resolved = preg_replace('#/+#', '/', $resolved);
        $real = realpath($resolved);

        if ($real === false) {
            return null;
        }

        $rootReal = realpath($root);
        if ($rootReal === false || ! Str::startsWith($real, $rootReal)) {
            return null;
        }

        return $real;
    }

    /** @return array<string> */
    public static function allowedDocumentExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip'];
    }

    /** @return array<string> */
    public static function allowedImageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }
}
