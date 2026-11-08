<?php

namespace App\Filament\Learn\Widgets;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Models\ReadingProgress;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * The student's most recently opened readings, to jump back in quickly.
 */
class ContinueReviewing extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Continue reviewing';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ReadingProgress::query()
                ->where('user_id', Auth::id())
                ->whereNotNull('last_viewed_at')
                ->whereHas('material.topic', fn (Builder $topic) => $topic
                    ->where('is_published', true)
                    ->whereHas('course', fn (Builder $course) => $course->visibleToStudents()))
                ->with('material.topic.course'))
            ->defaultSort('last_viewed_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('material.title')
                    ->label('Reading')
                    ->weight('semibold')
                    ->color('primary')
                    ->description(fn (ReadingProgress $record): string => $record->material->topic->title),
                TextColumn::make('material.topic.course.code')
                    ->label('Course')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->state(fn (ReadingProgress $record): string => $record->isCompleted() ? 'Done' : 'In progress')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Done' ? 'success' : 'warning'),
                TextColumn::make('last_viewed_at')
                    ->label('Last opened')
                    ->since()
                    ->color('gray'),
            ])
            ->recordUrl(fn (ReadingProgress $record): string => CourseResource::readUrl($record->material))
            ->emptyStateHeading('Nothing opened yet')
            ->emptyStateDescription('Pick a course and start with its first reading.');
    }
}
