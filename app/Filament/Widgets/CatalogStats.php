<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Material;
use App\Models\ReadingProgress;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Staff overview. Professors see figures for their own courses; admins see everything.
 */
class CatalogStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $courses = Course::query()->when(! $user->isAdmin(), fn (Builder $query) => $query->whereHas(
            'professors',
            fn (Builder $professors) => $professors->whereKey($user->getKey()),
        ));
        $courseIds = (clone $courses)->pluck('id');
        $materials = Material::query()->whereHas('topic', fn (Builder $topic) => $topic->whereIn('course_id', $courseIds));
        $materialIds = (clone $materials)->pluck('id');

        $stats = [
            Stat::make($user->isAdmin() ? 'Courses' : 'My courses', $courseIds->count())
                ->descriptionIcon(Heroicon::OutlinedBookOpen)
                ->description((clone $courses)->where('is_published', true)->count().' visible to students'),
            Stat::make('Lesson PDFs', $materialIds->count())
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->description(number_format((int) (clone $materials)->sum('page_count')).' pages'),
            Stat::make('Reads this week', ReadingProgress::query()
                ->whereIn('material_id', $materialIds)
                ->where('last_viewed_at', '>=', now()->subWeek())
                ->count())
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->description(ReadingProgress::query()->whereIn('material_id', $materialIds)->whereNotNull('completed_at')->count().' marked done overall')
                ->color('success'),
        ];

        if ($user->isAdmin()) {
            $stats[] = Stat::make('Students', User::where('role', UserRole::Student)->count())
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->description(User::where('role', UserRole::Professor)->count().' professors');
        }

        return $stats;
    }
}
