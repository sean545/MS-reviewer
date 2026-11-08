<?php

namespace App\Jobs;

use App\Models\Material;
use App\Models\MaterialPage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reads a lesson PDF's page count, size and per-page text, and renders page 1 as a cover thumbnail (poppler-utils).
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

        $this->indexPageText($pdfPath);

        $this->material->forceFill([
            'page_count' => $pageCount,
            'size_bytes' => $disk->size($this->material->file_path),
            'thumbnail_path' => $render->successful() && $disk->exists("{$thumbnailBase}.png") ? "{$thumbnailBase}.png" : null,
            'processed_at' => now(),
        ])->saveQuietly();
    }

    /**
     * Store each page's text for search. Scanned PDFs without a text layer simply yield no rows.
     */
    private function indexPageText(string $pdfPath): void
    {
        $result = Process::timeout(120)->run(['pdftotext', '-enc', 'UTF-8', $pdfPath, '-']);

        $this->material->pages()->delete();

        if (! $result->successful()) {
            return;
        }

        collect(explode("\f", $result->output()))
            ->map(fn (string $content, int $index): array => [
                'material_id' => $this->material->getKey(),
                'page_number' => $index + 1,
                'content' => Str::squish(mb_scrub($content, 'UTF-8')),
            ])
            ->reject(fn (array $page): bool => $page['content'] === '')
            ->chunk(200)
            ->each(fn ($pages) => MaterialPage::insert($pages->values()->all()));
    }
}
