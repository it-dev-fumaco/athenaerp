<?php

/**
 * Polyfill for a namespace call bug in buglinjo/laravel-webp.
 *
 * The package calls `imagewebp()` inside the `Buglinjo\LaravelWebp` namespace
 * without a leading backslash, so PHP tries to resolve it as a namespaced function
 * and crashes. Defining it here keeps vendor code untouched.
 *
 * If the global GD `imagewebp()` function is unavailable (GD built without WebP),
 * we throw a RuntimeException so the caller can fall back to original formats.
 */
namespace Buglinjo\LaravelWebp;

use RuntimeException;

if (! function_exists(__NAMESPACE__.'\\imagewebp')) {
    function imagewebp($image, string $outputPath, int $quality = 80): bool
    {
        if (! \function_exists('imagewebp')) {
            throw new RuntimeException('WebP is not supported by your PHP GD build (missing imagewebp).');
        }

        return (bool) \imagewebp($image, $outputPath, $quality);
    }
}

