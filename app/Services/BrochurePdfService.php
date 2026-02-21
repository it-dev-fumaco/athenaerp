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
                $fromAttrs = [];
                foreach ($row['attributes'] ?? [] as $att) {
                    $v = trim((string) ($att['attribute_value'] ?? ''));
                    if ($v !== '' && preg_match($imageExtensions, $v)) {
                        $fromAttrs[] = $v;
                    }
                }
                $idx = 0;
                for ($i = 1; $i <= 3; $i++) {
                    if (empty($filenames[$i]) && isset($fromAttrs[$idx])) {
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

                $absolutePath = $this->resolveAbsolutePathForPdf($row, $project, $isStandard, $i, $name);
                if ($absolutePath && file_exists($absolutePath)) {
                    $mime = @mime_content_type($absolutePath) ?: 'image/png';
                    $data = @file_get_contents($absolutePath);
                    if ($data !== false) {
                        $dataUri = 'data:'.$mime.';base64,'.base64_encode($data);
                        if ($i === 3 && isset($content[$r]['image_data_uris'][2]) && $content[$r]['image_data_uris'][2] === $dataUri) {
                            continue;
                        }
                        $content[$r]['image_data_uris'][$i] = $dataUri;
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Resolve standard (single-item) brochure images from upcloud for PDF embedding.
     * Writes JPEGs to temp files and passes local paths so DomPDF can load them reliably.
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
            if ($jpegData === null) {
                continue;
            }
            $tempFile = $tempDir.'/brochure_'.$i.'_'.Str::uuid().'.jpg';
            if (file_put_contents($tempFile, $jpegData) !== false) {
                $content[0]['image_local_paths'][$i] = $tempFile;
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
}
