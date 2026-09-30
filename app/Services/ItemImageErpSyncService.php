<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemImages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemImageErpSyncService
{
    /**
     * Map a tabItem Images.image_path value to the real Upcloud object key.
     * Prefer JPEG when it exists; fall back to the stored key / WebP.
     * Older rows store a filename only; objects live under img/.
     */
    public function resolveStorageKey(?string $imagePath): ?string
    {
        $imagePath = $imagePath ? trim((string) $imagePath) : null;
        if (! $imagePath) {
            return null;
        }

        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        $key = ltrim($imagePath, '/');
        $found = $this->firstExistingKey($this->storageKeyCandidates($key));
        if ($found !== null) {
            return $found;
        }

        if (! str_contains($key, '/')) {
            return 'img/'.$key;
        }

        return $key;
    }

    public function objectExists(?string $imagePath): bool
    {
        $imagePath = $imagePath ? trim((string) $imagePath) : null;
        if (! $imagePath || Str::startsWith($imagePath, ['http://', 'https://'])) {
            return false;
        }

        $key = ltrim($imagePath, '/');

        return $this->firstExistingKey($this->storageKeyCandidates($key)) !== null;
    }

    /**
     * @return list<string>
     */
    private function storageKeyCandidates(string $key): array
    {
        $base = basename($key);
        $stem = pathinfo($base, PATHINFO_FILENAME);
        $dirs = ['img', 'items', 'item-images'];
        $keyDir = str_contains($key, '/') ? dirname($key) : null;
        if (is_string($keyDir) && $keyDir !== '.' && ! in_array($keyDir, $dirs, true)) {
            array_unshift($dirs, $keyDir);
        }

        $candidates = [];
        foreach ($dirs as $dir) {
            $candidates[] = $dir.'/'.$stem.'.jpg';
            $candidates[] = $dir.'/'.$stem.'.jpeg';
        }

        $candidates[] = $key;
        if (! str_contains($key, '/')) {
            $candidates[] = 'img/'.$base;
            $candidates[] = 'items/'.$base;
            $candidates[] = 'item-images/'.$base;
        }

        foreach ($dirs as $dir) {
            $candidates[] = $dir.'/'.$stem.'.webp';
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  list<string>  $candidates
     */
    private function firstExistingKey(array $candidates): ?string
    {
        $disk = Storage::disk('upcloud');
        foreach ($candidates as $candidate) {
            try {
                if ($disk->exists($candidate)) {
                    return $candidate;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Set Upcloud object ACL to public-read so ERPNext can load the permanent URL.
     */
    public function makePublic(?string $imagePath): void
    {
        $imagePath = $imagePath ? trim((string) $imagePath) : null;
        if (! $imagePath || Str::startsWith($imagePath, ['http://', 'https://'])) {
            return;
        }

        $key = $this->resolveStorageKey($imagePath) ?? ltrim($imagePath, '/');

        try {
            Storage::disk('upcloud')->setVisibility($key, 'public');
        } catch (\Throwable $e) {
            Log::warning('Failed to set Upcloud object visibility to public', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Permanent (non-signed) Upcloud object URL for ERPNext.
     */
    public function permanentUrl(?string $imagePath): ?string
    {
        $imagePath = $imagePath ? trim((string) $imagePath) : null;
        if (! $imagePath) {
            return null;
        }

        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        $key = $this->resolveStorageKey($imagePath) ?? ltrim($imagePath, '/');

        return Storage::disk('upcloud')->url($key);
    }

    public function isAlreadyBackfilled(?string $image): bool
    {
        $image = $image ? trim((string) $image) : '';

        return Str::startsWith($image, ['http://', 'https://']) && str_contains($image, '/img/');
    }

    public function defaultImage(string $itemCode): ?ItemImages
    {
        return ItemImages::query()
            ->where('parent', $itemCode)
            ->orderBy('idx', 'asc')
            ->orderBy('name', 'asc')
            ->first();
    }

    /**
     * If the default image is WebP-only, convert it to JPEG on Upcloud for ERPNext.
     */
    public function ensureJpegForErp(?string $imagePath): void
    {
        $imagePath = $imagePath ? trim((string) $imagePath) : null;
        if (! $imagePath || Str::startsWith($imagePath, ['http://', 'https://'])) {
            return;
        }

        $disk = Storage::disk('upcloud');
        $sourceKey = $this->resolveStorageKey($imagePath) ?? ltrim($imagePath, '/');
        if (Str::startsWith($sourceKey, ['http://', 'https://'])) {
            return;
        }

        $stem = pathinfo($sourceKey, PATHINFO_FILENAME);
        $dir = dirname($sourceKey);
        $dir = ($dir === '.' ? 'img' : $dir);
        $jpegKey = $dir.'/'.$stem.'.jpg';

        try {
            if ($disk->exists($jpegKey)) {
                return;
            }
        } catch (\Throwable) {
            // Continue and try to convert.
        }

        if (! str_ends_with(strtolower($sourceKey), '.webp')) {
            return;
        }

        if (! function_exists('imagejpeg') || ! function_exists('imagecreatefromstring')) {
            Log::warning('Cannot convert WebP default image to JPEG: GD imagejpeg is unavailable.', [
                'key' => $sourceKey,
            ]);

            return;
        }

        try {
            $bytes = $disk->get($sourceKey);
            $gd = @imagecreatefromstring($bytes);
            if ($gd === false) {
                throw new \RuntimeException('Unable to decode WebP for JPEG conversion.');
            }

            if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($gd)) {
                imagepalettetotruecolor($gd);
            }

            $tempPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'athena-erp-'.uniqid('', true).'.jpg';
            try {
                if (! imagejpeg($gd, $tempPath, 85)) {
                    throw new \RuntimeException('Failed to encode JPEG.');
                }
                $stream = fopen($tempPath, 'rb');
                if ($stream === false) {
                    throw new \RuntimeException('Failed to read converted JPEG.');
                }
                try {
                    $disk->put($jpegKey, $stream, ['visibility' => 'public']);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            } finally {
                imagedestroy($gd);
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to convert WebP default image to JPEG', [
                'source' => $sourceKey,
                'jpeg' => $jpegKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Keep tabItem.image aligned with the current default (lowest idx) image.
     */
    public function syncDefaultImage(string $itemCode, ?string $modifiedBy = null): ?string
    {
        $default = $this->defaultImage($itemCode);

        if ($default?->image_path) {
            $this->ensureJpegForErp($default->image_path);
            $this->makePublic($default->image_path);
        }

        $publicUrl = $default
            ? ($this->permanentUrl($default->image_path) ?: $default->public_url)
            : null;

        $now = now()->toDateTimeString();

        if ($default && $publicUrl && $default->public_url !== $publicUrl) {
            ItemImages::query()
                ->where('parent', $itemCode)
                ->where('name', $default->name)
                ->update([
                    'public_url' => $publicUrl,
                    'modified' => $now,
                    'modified_by' => $modifiedBy,
                ]);
        }

        Item::query()
            ->where('name', $itemCode)
            ->update([
                'image' => $publicUrl,
                'modified' => $now,
                'modified_by' => $modifiedBy,
            ]);

        return $publicUrl;
    }
}
