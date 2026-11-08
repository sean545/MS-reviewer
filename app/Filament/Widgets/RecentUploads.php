<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Courses\CourseResource;
use App\Models\Material;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Latest lesson PDFs, so staff can confirm uploads were processed.
 */
class RecentUploads extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent uploads';

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->query(fn (): Builder => Material::query()
                ->with(['topic.course', 'uploader'])
                ->when(! $user->isAdmin(), fn (Builder $query) => $query->whereHas(
                    'topic.course.professors',
                    fn (Builder $professors) => $professors->whereKey($user->getKey()),
                )))
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('title')
                    ->weight('semibold')
                    ->description(fn (Material $record): string => $record->topic->title),
                TextColumn::make('topic.course.code')
                    ->label('Course')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('details')
                    ->label('File')
                    ->state(fn (Material $record): string => $record->details()),
                TextColumn::make('uploader.name')
                    ->label('By')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->since(),
            ])
            ->recordUrl(fn (Material $record): string => CourseResource::getUrl('edit', ['record' => $record->topic->course]))
            ->emptyStateHeading('No PDFs uploaded yet');
    }
}
