<?php

namespace App\Filament\Learn\Resources\Courses\Pages;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Models\Course;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->load([
            'publishedTopics.materials.topic.course',
            'publishedTopics.materials.progress' => fn (HasMany $progress) => $progress->where('user_id', Auth::id()),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        /** @var Course $course */
        $course = $this->getRecord();

        return $course->displayName();
    }
}
