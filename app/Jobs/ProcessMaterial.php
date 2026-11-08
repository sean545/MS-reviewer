<?php

namespace App\Jobs;

use App\Models\Material;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Reads a lesson PDF's page count and size and renders its first page as a cover thumbnail (poppler-utils).
 */
class ProcessMaterial implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public Material $material) {}

    public function handle(): void
    {
        $disk = Storage::disk(Material::DISK);

        if (! $disk->exists($this->material->file_path)) {
            return;
        }

        $pdfPath = $disk->path($this->material->file_path);

        $info = Process::timeout(60)->run(['pdfinfo', $pdfPath]);
        $pageCount = preg_match('/^Pages:\s+(\d+)/m', $info->output(), $matches) === 1 ? (int) $matches[1] : null;

        $thumbnailBase = "materials/thumbnails/{$this->material->getKey()}";
        $disk->makeDirectory('materials/thumbnails');
        $render = Process::timeout(120)->run([
            'pdftoppm', '-png', '-singlefile', '-f', '1', '-l', '1', '-scale-to', '480',
            $pdfPath, $disk->path($thumbnailBase),
        ]);

        $this->material->forceFill([
            'page_count' => $pageCount,
            'size_bytes' => $disk->size($this->material->file_path),
            'thumbnail_path' => $render->successful() && $disk->exists("{$thumbnailBase}.png") ? "{$thumbnailBase}.png" : null,
            'processed_at' => now(),
        ])->saveQuietly();
    }
}
