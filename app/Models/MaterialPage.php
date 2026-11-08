<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Extracted text of a single PDF page, for search.
 */
#[Fillable(['material_id', 'page_number', 'content'])]
class MaterialPage extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Pages of PDFs that students are allowed to read.
     *
     * @param  Builder<MaterialPage>  $query
     */
    #[Scope]
    protected function visibleToStudents(Builder $query): void
    {
        $query->whereHas('material.topic', fn (Builder $topic) => $topic
            ->where('is_published', true)
            ->whereHas('course', fn (Builder $course) => $course->visibleToStudents()));
    }

    /**
     * HTML-safe excerpt around the search term with matches wrapped in <mark>.
     */
    public function highlightedExcerpt(?string $term): string
    {
        $term = trim((string) $term);
        $excerpt = $term !== '' ? Str::excerpt($this->content, $term, ['radius' => 140]) : null;
        $escaped = e($excerpt ?? Str::limit($this->content, 280));

        if ($term === '') {
            return $escaped;
        }

        return (string) preg_replace('/'.preg_quote(e($term), '/').'/iu', '<mark>$0</mark>', $escaped);
    }
}
