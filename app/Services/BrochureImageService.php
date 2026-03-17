<?php

namespace App\Services;

use Buglinjo\LaravelWebp\Facades\Webp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BrochureImageService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'webp', 'WEBP'];

    private const DISK = 'upcloud';

    /**
     * Store an uploaded image for spreadsheet brochure (no WebP conversion).
     * Stores to upcloud (S3 or local) so the PDF can load it. Returns the stored filename.
     *
     * @throws RuntimeException when storage fails
     */
    public function storeSpreadsheetImage(UploadedFile $file, string $folder): string
    {
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            .'.'.$file->getClientOriginalExtension();
        $key = 'item-brochures/'.strtoupper($folder).'/'.$filename;

        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            throw new RuntimeException('Failed to open uploaded file.');
        }
        try {
            $success = Storage::disk(self::DISK)->put($key, $stream);
            if (! $success) {
                throw new RuntimeException('Failed to store brochure image.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $filename;
    }

    /**
     * Get stored filename for an existing item image (prefer WebP if present).
     */
    public function getExistingImageStoredFilename(?string $filename, string $imagePath = 'img/'): ?string
    {
        if (! $filename) {
            return null;
        }
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $webpFilename = $base.'.webp';

        return Storage::disk(self::DISK)->exists($imagePath.$webpFilename)
            ? $webpFilename
            : $filename;
    }

    /**
     * Convert uploaded file to WebP (when possible) and store to upcloud.
     * Returns ['storedFilename' => string, 'imagePath' => string].
     * On WebP failure, stores original file. Throws on storage failure.
     *
     * @return array{storedFilename: string, imagePath: string}
     *
     * @throws RuntimeException when storage write fails
     */
    public function convertToWebpAndStore(UploadedFile $file, string $pathPrefix = 'item-brochures/'): array
    {
        $pathPrefix = rtrim($pathPrefix, '/').'/';
        $originalName = $file->getClientOriginalName();
        $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $filename = str_replace(' ', '-', $filename);
        $extension = $file->getClientOriginalExtension();
        $webpFilename = $filename.'.webp';

        $shouldCleanupWebp = false;
        try {
            if (strtolower($fileExt) !== 'webp') {
                $webp = Webp::make($file);
                $tempDir = public_path('temp');
                if (! File::exists($tempDir)) {
                    File::makeDirectory($tempDir, 0755, true);
                }
                $webpPath = $tempDir.'/'.$webpFilename;
                $webp->save($webpPath);
                $shouldCleanupWebp = true;
            } else {
                $webpPath = $file->getPathname();
            }

            $this->putFileContentsToStorage($webpPath, $pathPrefix.$webpFilename);
            $storedFilename = $webpFilename;

            if ($shouldCleanupWebp && File::exists($webpPath)) {
                @unlink($webpPath);
            }
        } catch (\Throwable $e) {
            Log::warning('Brochure WebP conversion failed, saving original image', [
                'error' => $e->getMessage(),
                'original_name' => $originalName,
            ]);
            $storedFilename = $filename.'.'.$extension;
            $this->putFileContentsToStorage($file->getPathname(), $pathPrefix.$storedFilename);
        }

        return [
            'storedFilename' => $storedFilename,
            'imagePath' => $pathPrefix,
        ];
    }

    /**
     * Validate that the uploaded file has an allowed image extension.
     */
    public function validateImageExtension(UploadedFile $file): bool
    {
        $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

        return in_array($ext, self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Allowed extensions for brochure images (for validation messages).
     *
     * @return array<string>
     */
    public static function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * Write file contents to storage disk. Throws on failure.
     *
     * @throws RuntimeException when put fails
     */
    private function putFileContentsToStorage(string $localPath, string $storagePath): void
    {
        $stream = fopen($localPath, 'r');
        if ($stream === false) {
            throw new RuntimeException('Failed to open file for reading: '.$localPath);
        }
        try {
            $success = Storage::disk(self::DISK)->put($storagePath, $stream);
            if (! $success) {
                throw new RuntimeException('Failed to write file to storage: '.$storagePath);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
