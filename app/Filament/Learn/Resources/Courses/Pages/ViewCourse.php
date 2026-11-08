<?php

namespace App\Filament\Learn\Resources\Courses\Pages;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Models\Course;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Course $course */
        $course = $this->getRecord();

        return $course->displayName();
    }
}
