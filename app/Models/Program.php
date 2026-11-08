<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A graduate degree program (e.g. MS Data Science) that groups its courses.
 */
#[Fillable(['code', 'name', 'slug', 'description', 'sort_order', 'is_published'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    /**
     * Delete courses one by one so their topics' PDFs are removed from storage too.
     */
    protected static function booted(): void
    {
        static::deleting(fn (Program $program) => $program->courses()->get()->each->delete());
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class)->orderBy('sort_order');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
