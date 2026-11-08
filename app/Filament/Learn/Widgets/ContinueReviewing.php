<?php

namespace App\Filament\Learn\Widgets;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Models\ReadingProgress;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * The student's most recently opened readings as cards, to jump back in quickly.
 */
class ContinueReviewing extends TableWidget
{
    protected static ?int $sort = 1;

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
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->paginated([3, 6, 12])
            ->defaultPaginationPageOption(3)
            ->columns([
                Stack::make([
                    ImageColumn::make('thumbnail')
                        ->state(fn (ReadingProgress $record): ?string => $record->material->thumbnail_path !== null
                            ? route('materials.thumbnail', $record->material)
                            : null)
                        ->imageHeight(140)
                        ->imageWidth('100%')
                        ->extraImgAttributes(['class' => 'object-cover object-top', 'alt' => ''])
                        ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5']),
                    Stack::make([
                        TextColumn::make('material.topic.course.code')
                            ->badge(),
                        TextColumn::make('material.title')
                            ->weight('bold')
                            ->wrap(),
                        TextColumn::make('material.topic.title')
                            ->color('gray')
                            ->size('sm'),
                        TextColumn::make('status')
                            ->state(fn (ReadingProgress $record): string => $record->isCompleted() ? 'Done' : 'In progress')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Done' ? 'success' : 'warning'),
                        TextColumn::make('last_viewed_at')
                            ->since()
                            ->prefix('Opened ')
                            ->color('gray')
                            ->size('sm'),
                    ])->space(2)->extraAttributes(['class' => 'p-4']),
                ]),
            ])
            ->recordUrl(fn (ReadingProgress $record): string => CourseResource::readUrl($record->material))
            ->emptyStateHeading('Nothing opened yet')
            ->emptyStateDescription('Pick a course and start with its first reading.');
    }
}
