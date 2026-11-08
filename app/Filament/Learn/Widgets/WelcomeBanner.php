<?php

namespace App\Filament\Learn\Widgets;

use App\Filament\Learn\Pages\SearchReadings;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Greeting, quick PDF search and the student's review totals at the top of the dashboard.
 */
class WelcomeBanner extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.learn.widgets.welcome-banner';

    /**
     * @return array{firstName: string, completedPdfs: int, totalPdfs: int, coursesStarted: int, notes: int, searchUrl: string}
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $readable = Material::query()->whereHas('topic', fn (Builder $topic) => $topic
            ->where('is_published', true)
            ->whereHas('course', fn (Builder $course) => $course->visibleToStudents()));

        return [
            'firstName' => Str::of($user->name)->before(' ')->toString(),
            'totalPdfs' => (clone $readable)->count(),
            'completedPdfs' => (clone $readable)
                ->whereHas('progress', fn (Builder $progress) => $progress->whereBelongsTo($user)->whereNotNull('completed_at'))
                ->count(),
            'coursesStarted' => Course::query()->visibleToStudents()
                ->whereHas('publishedMaterials.progress', fn (Builder $progress) => $progress->whereBelongsTo($user))
                ->count(),
            'notes' => $user->notes()->count(),
            'searchUrl' => SearchReadings::getUrl(),
        ];
    }
}
