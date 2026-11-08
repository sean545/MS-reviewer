<?php

namespace App\Filament\Learn\Widgets;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $readable = Material::query()->whereHas('topic', fn (Builder $topic) => $topic
            ->where('is_published', true)
            ->whereHas('course', fn (Builder $course) => $course->visibleToStudents()));
        $totalPdfs = (clone $readable)->count();
        $completedPdfs = (clone $readable)
            ->whereHas('progress', fn (Builder $progress) => $progress->whereBelongsTo($user)->whereNotNull('completed_at'))
            ->count();
        $coursesStarted = Course::query()->visibleToStudents()
            ->whereHas('publishedMaterials.progress', fn (Builder $progress) => $progress->whereBelongsTo($user))
            ->count();

        return [
            Stat::make('PDFs reviewed', "{$completedPdfs} / {$totalPdfs}")
                ->description($totalPdfs > 0 ? round($completedPdfs / $totalPdfs * 100).'% of all readings' : 'No readings posted yet')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Courses started', $coursesStarted)
                ->description('Courses with at least one opened PDF')
                ->descriptionIcon(Heroicon::OutlinedBookOpen),
            Stat::make('Notes', $user->notes()->count())
                ->description('Private notes and bookmarks')
                ->descriptionIcon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
