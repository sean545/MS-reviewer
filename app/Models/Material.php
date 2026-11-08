<?php

namespace App\Models;

use App\Jobs\ProcessMaterial;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * A lesson PDF. New or replaced files are queued for processing (page count + cover thumbnail).
 */
#[Fillable(['topic_id', 'uploaded_by', 'title', 'file_path', 'original_name', 'sort_order'])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    public const DISK = 'local';

    protected static function booted(): void
    {
        static::updated(function (Material $material): void {
            if ($material->wasChanged('file_path')) {
                Storage::disk(self::DISK)->delete(array_filter([
                    $material->getOriginal('file_path'),
                    $material->getOriginal('thumbnail_path'),
                ]));
            }
        });

        static::saved(function (Material $material): void {
            if ($material->wasRecentlyCreated || $material->wasChanged('file_path')) {
                ProcessMaterial::dispatch($material);
            }
        });

        static::deleted(function (Material $material): void {
            Storage::disk(self::DISK)->delete(array_filter([$material->file_path, $material->thumbnail_path]));
        });
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Students may open this PDF: its topic is published and its course is visible to students.
     */
    public function isVisibleToStudents(): bool
    {
        return $this->topic->is_published
            && Course::query()->visibleToStudents()->whereKey($this->topic->course_id)->exists();
    }

    /**
     * Summary line such as "12 pages · 1.4 MB".
     */
    public function details(): string
    {
        return collect([
            $this->page_count !== null ? Str::plural('page', $this->page_count, prependCount: true) : null,
            $this->size_bytes !== null ? Number::fileSize($this->size_bytes, precision: 1) : null,
        ])->filter()->implode(' · ') ?: 'Processing…';
    }

    public function downloadName(): string
    {
        return $this->original_name ?: Str::slug($this->title).'.pdf';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'size_bytes' => 'integer',
            'page_count' => 'integer',
        ];
    }
}
