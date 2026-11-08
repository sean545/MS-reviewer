<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notice for students. Without a course it is site-wide; scheduled ones appear at published_at.
 */
#[Fillable(['course_id', 'author_id', 'title', 'body', 'is_pinned', 'published_at'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Published by now and either site-wide or for a course students can see.
     *
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function visibleToStudents(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $scope) => $scope
                ->whereNull('course_id')
                ->orWhereHas('course', fn (Builder $course) => $course->visibleToStudents()));
    }

    /**
     * Pinned first, then newest.
     *
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    public function audienceLabel(): string
    {
        return $this->course?->code ?? 'All students';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
