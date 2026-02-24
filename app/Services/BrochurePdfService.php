<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrochurePdfService
{
    private const DISK = 'upcloud';

    /**
     * Resolve brochure image paths and attach base64 data URIs so the PDF view can embed images reliably.
     *
     * @param  array<int, array<string, mixed>>  $content
     * @return array<int, array<string, mixed>>
     */
    public function resolveBrochureImagePathsForPdf(array $content, string $project, bool $isStandard): array
    {
        $imageExtensions = '/\.(png|jpg|jpeg|gif|webp|bmp)$/i';

        foreach ($content as $r => $row) {
            $content[$r]['image_data_uris'] = [1 => null, 2 => null, 3 => null];

            $filenames = [];
            if ($isStandard) {
                for ($i = 1; $i <= 3; $i++) {
                    $fp = $row['images']['image'.$i]['filepath'] ?? null;
                    $filenames[$i] = $fp ? basename($fp) : null;
                }
            } else {
                for ($i = 1; $i <= 3; $i++) {
                    $val = $row['images']['image'.$i] ?? null;
                    $filenames[$i] = $val ? trim((string) $val) : null;
                }
                // Only fill from attributes when this slot has no dedicated Image N column (old Excel format).
                // If the slot has a dedicated column and it's empty, the user removed the image — don't overwrite.
                $fromAttrs = [];
                foreach ($row['attributes'] ?? [] as $att) {
                    $v = trim((string) ($att['attribute_value'] ?? ''));
                    if ($v !== '' && preg_match($imageExtensions, $v)) {
                        $fromAttrs[] = $v;
                    }
                }
                $idx = 0;
                for ($i = 1; $i <= 3; $i++) {
                    $hasDedicatedColumn = array_key_exists('image'.$i, $row['images'] ?? []);
                    if (! $hasDedicatedColumn && isset($fromAttrs[$idx])) {
                        $filenames[$i] = $fromAttrs[$idx];
                        $idx++;
                    }
                }
            }

            for ($i = 1; $i <= 3; $i++) {
                $name = $filenames[$i] ?? null;
                if (! $name) {
                    continue;
                }

                if ($i === 3 && isset($filenames[2]) && $filenames[2] !== null && $name === $filenames[2]) {
                    if ($isStandard && isset($content[$r]['images']['image3'])) {
                        $content[$r]['images']['image3'] = ['id' => null, 'filepath' => null];
                    }
                    continue;
                }
                if ($i === 3 && isset($filenames[1]) && $filenames[1] !== null && $name === $filenames[1]) {
                    if ($isStandard && isset($content[$r]['images']['image3'])) {
                        $content[$r]['images']['image3'] = ['id' => null, 'filepath' => null];
                    }
                    continue;
                }

                $absolutePath = $this->resolveAbsolutePathForPdf($row, $project, $isStandard, $i, $name);
                $dataUri = null;
                if ($absolutePath && file_exists($absolutePath)) {
                    $mime = @mime_content_type($absolutePath) ?: 'image/png';
                    $data = @file_get_contents($absolutePath);
                    if ($data !== false) {
                        $dataUri = 'data:'.$mime.';base64,'.base64_encode($data);
                    }
                } elseif (! $isStandard) {
                    try {
                        $dataUri = $this->resolveImageDataUriFromDisk($project, $name);
                        if ($dataUri === null && isset($row['project']) && trim((string) $row['project']) !== '' && strtoupper(trim($row['project'])) !== strtoupper($project)) {
                            $dataUri = $this->resolveImageDataUriFromDisk(trim($row['project']), $name);
                        }
                    } catch (\Throwable $e) {
                        $dataUri = null;
                    }
                }
                if ($dataUri !== null) {
                    $isDuplicateOfSlot2 = $i === 3 && isset($content[$r]['image_data_uris'][2]) && $content[$r]['image_data_uris'][2] === $dataUri;
                    $isDuplicateOfSlot1 = $i === 3 && isset($content[$r]['image_data_uris'][1]) && $content[$r]['image_data_uris'][1] === $dataUri;
                    if ($isDuplicateOfSlot2 || $isDuplicateOfSlot1) {
                        // skip duplicate so slot 3 stays empty or gets a different image later
                    } else {
                        $content[$r]['image_data_uris'][$i] = $dataUri;
                    }
                }
            }

            if (! $isStandard) {
                try {
                    $this->fillMissingLoopBrochureImagesFromFolder($content[$r], $project, $row);
                } catch (\Throwable $e) {
                    // avoid breaking PDF generation
                }
            }
        }

        return $content;
    }

    /**
     * For loop brochure: fill only slots that had a filename in the Excel but we failed to resolve.
     * Do not fill slots that are empty in the Excel (user removed the image).
     */
    private function fillMissingLoopBrochureImagesFromFolder(array &$row, string $project, array $fullRow): void
    {
        if (! isset($row['image_data_uris']) || ! is_array($row['image_data_uris'])) {
            $row['image_data_uris'] = [1 => null, 2 => null, 3 => null];
        }
        $uris = &$row['image_data_uris'];
        $projectNorm = strtoupper(trim($project));
        $dir = 'item-brochures/'.$projectNorm;
        $disk = Storage::disk(self::DISK);

        // Only consider a slot fillable if the Excel had a filename for it (resolution failed). Empty cell = user removed image.
        $slotHadFilename = [];
        for ($s = 1; $s <= 3; $s++) {
            $val = $fullRow['images']['image'.$s] ?? null;
            $slotHadFilename[$s] = $val !== null && trim((string) $val) !== '';
        }

        $allFiles = [];
        try {
            $allFiles = $disk->files($dir);
            if (empty($allFiles)) {
                $allFiles = $disk->allFiles($dir);
            }
        } catch (\Throwable $e) {
            return;
        }

        $imageExtensions = '/\.(png|jpg|jpeg|gif|webp|bmp)$/i';
        $filled = 0;
        foreach ($allFiles as $fileKey) {
            if ($filled >= 3) {
                break;
            }
            $fullKey = is_string($fileKey) && str_starts_with($fileKey, 'item-brochures') ? $fileKey : $dir.'/'.ltrim((string) $fileKey, '/');
            $fileName = pathinfo($fullKey, PATHINFO_BASENAME);
            if (! preg_match($imageExtensions, $fileName)) {
                continue;
            }
            $nextSlot = null;
            for ($s = 1; $s <= 3; $s++) {
                if (empty($uris[$s]) && ! empty($slotHadFilename[$s])) {
                    $nextSlot = $s;
                    break;
                }
            }
            if ($nextSlot === null) {
                break;
            }
            try {
                $data = $disk->get($fullKey);
            } catch (\Throwable $e) {
                continue;
            }
            if ($data === null || $data === '') {
                continue;
            }
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $mimeMap = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
            ];
            $mime = $mimeMap[$ext] ?? 'image/png';
            $dataUri = 'data:'.$mime.';base64,'.base64_encode($data);
            $alreadyUsed = in_array($dataUri, [$uris[1] ?? null, $uris[2] ?? null, $uris[3] ?? null], true);
            if (! $alreadyUsed) {
                $uris[$nextSlot] = $dataUri;
                $filled++;
            }
        }
    }

    /**
     * Resolve standard (single-item) brochure images from upcloud for PDF embedding.
     * Sets image_data_uris (base64) so DomPDF can embed images without relying on local files.
     *
     * @param  array<int, array<string, mixed>>  $content
     * @param  array<string, array{filepath?: string|null}>  $images
     * @return array<int, array<string, mixed>>
     */
    public function resolveStandardBrochureImageDataUris(array $content, array $images): array
    {
        if (empty($content)) {
            return $content;
        }
        $content[0]['image_data_uris'] = [1 => null, 2 => null, 3 => null];
        $content[0]['image_local_paths'] = [1 => null, 2 => null, 3 => null];
        $disk = Storage::disk(self::DISK);
        $tempDir = storage_path('app/temp/brochure-pdf');
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
        ];
        for ($i = 1; $i <= 3; $i++) {
            $key = $images['image'.$i]['filepath'] ?? null;
            if (! $key) {
                continue;
            }
            $data = $disk->get($key);
            if ($data === null || $data === '') {
                continue;
            }
            $jpegData = $this->imageDataToJpegBytes($data, $key);
            if ($jpegData !== null) {
                $content[0]['image_data_uris'][$i] = 'data:image/jpeg;base64,'.base64_encode($jpegData);
                $tempFile = $tempDir.'/brochure_'.$i.'_'.Str::uuid().'.jpg';
                if (file_put_contents($tempFile, $jpegData) !== false) {
                    $content[0]['image_local_paths'][$i] = $tempFile;
                }
            } else {
                $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
                $mime = $mimeMap[$ext] ?? 'image/png';
                $content[0]['image_data_uris'][$i] = 'data:'.$mime.';base64,'.base64_encode($data);
            }
        }

        return $content;
    }

    /**
     * Convert image bytes to JPEG bytes; resizes large images. Returns raw JPEG string or null.
     */
    public function imageDataToJpegBytes(string $data, string $pathOrKey): ?string
    {
        $img = @imagecreatefromstring($data);
        if ($img === false) {
            return null;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $maxDim = 800;
        if ($w > $maxDim || $h > $maxDim) {
            $ratio = min($maxDim / $w, $maxDim / $h);
            $nw = (int) round($w * $ratio);
            $nh = (int) round($h * $ratio);
            $resized = imagecreatetruecolor($nw, $nh);
            if ($resized !== false) {
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($img);
                $img = $resized;
            }
        }
        ob_start();
        imagejpeg($img, null, 85);
        $jpegData = ob_get_clean();
        imagedestroy($img);
        if ($jpegData === false || $jpegData === '') {
            return null;
        }

        return $jpegData;
    }

    /**
     * Resolve absolute filesystem path for an image slot in a brochure row.
     */
    private function resolveAbsolutePathForPdf(array $row, string $project, bool $isStandard, int $i, string $name): ?string
    {
        if ($isStandard) {
            $filepath = $row['images']['image'.$i]['filepath'] ?? null;
            if (! $filepath) {
                return null;
            }
            $absolutePath = public_path($filepath);
            if (! file_exists($absolutePath)) {
                $absolutePath = storage_path('app/public/'.preg_replace('#^storage/#', '', $filepath));
            }

            return $absolutePath;
        }

        $disk = Storage::disk(self::DISK);
        $tryPaths = [
            $disk->path('item-brochures/'.strtoupper($project).'/'.$name),
            $disk->path('item-brochures/'.$name),
        ];
        foreach ($tryPaths as $p) {
            if ($p && file_exists($p)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Fetch image from upcloud disk (e.g. S3) by key and return a data URI, or null if not found.
     * Used when resolveAbsolutePathForPdf fails (e.g. disk is S3 and has no local path).
     */
    private function resolveImageDataUriFromDisk(string $project, string $name): ?string
    {
        try {
            return $this->resolveImageDataUriFromDiskInternal($project, $name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveImageDataUriFromDiskInternal(string $project, string $name): ?string
    {
        $disk = Storage::disk(self::DISK);
        $tryKeys = [
            'item-brochures/'.strtoupper($project).'/'.$name,
            'item-brochures/'.$name,
        ];
        foreach ($tryKeys as $key) {
            if (! $disk->exists($key)) {
                continue;
            }
            $data = $disk->get($key);
            if ($data === null || $data === '') {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mimeMap = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
            ];
            $mime = $mimeMap[$ext] ?? 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($data);
        }
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $dir = 'item-brochures/'.strtoupper($project);
        $candidates = $disk->files($dir);
        foreach ($candidates as $fileKey) {
            $fullKey = str_starts_with((string) $fileKey, $dir) ? $fileKey : $dir.'/'.ltrim((string) $fileKey, '/');
            $fileName = pathinfo($fullKey, PATHINFO_BASENAME);
            $fileBase = pathinfo($fileName, PATHINFO_FILENAME);
            if ($fileBase === $baseName || str_starts_with($fileBase, $baseName.' ')) {
                $data = $disk->get($fullKey);
                if ($data !== null && $data !== '') {
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $mimeMap = [
                        'png' => 'image/png',
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        'bmp' => 'image/bmp',
                    ];
                    $mime = $mimeMap[$ext] ?? 'image/png';

                    return 'data:'.$mime.';base64,'.base64_encode($data);
                }
            }
        }
        $localPaths = [
            public_path('item-brochures/'.strtoupper($project).'/'.$name),
            public_path('item-brochures/'.$name),
            storage_path('app/public/item-brochures/'.strtoupper($project).'/'.$name),
            storage_path('app/public/item-brochures/'.$name),
        ];
        foreach ($localPaths as $localPath) {
            if ($localPath && is_file($localPath)) {
                $data = @file_get_contents($localPath);
                if ($data !== false && $data !== '') {
                    $mime = @mime_content_type($localPath) ?: 'image/png';

                    return 'data:'.$mime.';base64,'.base64_encode($data);
                }
            }
        }
        $localDir = public_path('item-brochures/'.strtoupper($project));
        if (is_dir($localDir)) {
            $baseName = pathinfo($name, PATHINFO_FILENAME);
            foreach (new \DirectoryIterator($localDir) as $file) {
                if ($file->isDot() || ! $file->isFile()) {
                    continue;
                }
                $fileBase = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                if ($fileBase === $baseName || str_starts_with($fileBase, $baseName.' ')) {
                    $localPath = $file->getPathname();
                    $data = @file_get_contents($localPath);
                    if ($data !== false && $data !== '') {
                        $mime = @mime_content_type($localPath) ?: 'image/png';

                        return 'data:'.$mime.';base64,'.base64_encode($data);
                    }
                }
            }
        }

        return null;
    }
}
