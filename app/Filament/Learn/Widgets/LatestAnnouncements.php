<?php

namespace App\Filament\Learn\Widgets;

use App\Models\Announcement;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Site-wide and course announcements for students, pinned first.
 */
class LatestAnnouncements extends TableWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Announcements';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Announcement::query()->visibleToStudents()->latestFirst()->with('course'))
            ->paginated([3, 10])
            ->defaultPaginationPageOption(3)
            ->columns([
                Stack::make([
                    TextColumn::make('title')
                        ->weight('bold')
                        ->icon(fn (Announcement $record): ?Heroicon => $record->is_pinned ? Heroicon::Bookmark : null)
                        ->iconColor('primary'),
                    TextColumn::make('body')
                        ->wrap()
                        ->color('gray'),
                    TextColumn::make('meta')
                        ->state(fn (Announcement $record): string => "{$record->audienceLabel()} · {$record->published_at->diffForHumans()}")
                        ->size('sm')
                        ->color('primary'),
                ])->space(1),
            ])
            ->emptyStateHeading('No announcements')
            ->emptyStateIcon(Heroicon::OutlinedMegaphone);
    }
}
