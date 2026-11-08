<?php

namespace App\Models;

use App\Enums\CourseCategory;
use App\Enums\UserRole;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['program_id', 'code', 'title', 'slug', 'units', 'category', 'description', 'sort_order', 'is_published'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * Delete topics one by one so their PDFs are removed from storage too.
     */
    protected static function booted(): void
    {
        static::deleting(fn (Course $course) => $course->topics()->get()->each->delete());
    }

    /**
     * Course pages use readable URLs, e.g. /learn/courses/ds-230-machine-learning.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_professor')
            ->where('role', UserRole::Professor)
            ->withTimestamps();
    }

    /**
     * @return HasMany<Topic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Topic, $this>
     */
    public function publishedTopics(): HasMany
    {
        return $this->topics()->where('is_published', true);
    }

    /**
     * @return HasMany<Announcement, $this>
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * PDFs in this course's published topics.
     *
     * @return HasManyThrough<Material, Topic, $this>
     */
    public function publishedMaterials(): HasManyThrough
    {
        return $this->hasManyThrough(Material::class, Topic::class)->where('topics.is_published', true);
    }

    /**
     * Published PDFs in reading order (topic order, then material order).
     *
     * @return list<int>
     */
    public function readingOrder(): array
    {
        return Material::query()
            ->join('topics', 'topics.id', '=', 'materials.topic_id')
            ->where('topics.course_id', $this->getKey())
            ->where('topics.is_published', true)
            ->orderBy('topics.sort_order')->orderBy('topics.id')
            ->orderBy('materials.sort_order')->orderBy('materials.id')
            ->pluck('materials.id')
            ->all();
    }

    public function isHandledBy(User $user): bool
    {
        return $this->professors()->whereKey($user->getKey())->exists();
    }

    /**
     * Courses students can see: published, in a published program.
     *
     * @param  Builder<Course>  $query
     */
    #[Scope]
    protected function visibleToStudents(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereHas('program', fn (Builder $program) => $program->where('is_published', true));
    }

    /**
     * Full display name, e.g. "DS 201 · Statistical Inference".
     */
    public function displayName(): string
    {
        return "{$this->code} · {$this->title}";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => CourseCategory::class,
            'is_published' => 'boolean',
            'units' => 'integer',
        ];
    }
}
