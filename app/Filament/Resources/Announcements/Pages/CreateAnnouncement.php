<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Course;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $user */
        $user = Auth::user();

        static::ensureMayAnnounceTo($user, $data['course_id'] ?? null);

        return [...$data, 'author_id' => $user->getKey()];
    }

    /**
     * Guards against a professor posting site-wide or to a course they don't handle.
     */
    public static function ensureMayAnnounceTo(User $user, mixed $courseId): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $course = filled($courseId) ? Course::find($courseId) : null;

        if ($course === null || ! $course->isHandledBy($user)) {
            throw ValidationException::withMessages(['data.course_id' => 'Choose one of your courses.']);
        }
    }
}
