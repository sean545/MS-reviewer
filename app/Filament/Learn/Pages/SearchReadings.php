<?php

namespace App\Filament\Learn\Pages;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Models\MaterialPage;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Full-text search across the extracted text of every published PDF page.
 */
class SearchReadings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Search';

    protected static ?string $title = 'Search readings';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.learn.pages.search-readings';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => MaterialPage::query()
                ->visibleToStudents()
                ->with('material.topic.course')
                ->when(blank($this->getTableSearch()), fn (Builder $query) => $query->whereRaw('1 = 0')))
            ->searchPlaceholder('Search inside all PDFs, e.g. "gradient descent"')
            ->searchDebounce('400ms')
            ->persistSearchInSession()
            ->defaultSort('material_id')
            ->columns([
                TextColumn::make('material.title')
                    ->label('Reading')
                    ->weight('semibold')
                    ->color('primary')
                    ->description(fn (MaterialPage $record): string => "{$record->material->topic->course->code} · {$record->material->topic->title}")
                    ->searchable(),
                TextColumn::make('page_number')
                    ->label('Page')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('content')
                    ->label('Match')
                    ->html()
                    ->wrap()
                    ->formatStateUsing(fn (MaterialPage $record): string => $record->highlightedExcerpt($this->getTableSearch()))
                    ->searchable(),
            ])
            ->recordUrl(fn (MaterialPage $record): string => CourseResource::readUrl($record->material, $record->page_number))
            ->emptyStateIcon(Heroicon::OutlinedMagnifyingGlass)
            ->emptyStateHeading(fn (): string => blank($this->getTableSearch()) ? 'Search your readings' : 'No matches')
            ->emptyStateDescription(fn (): string => blank($this->getTableSearch())
                ? 'Type a word or phrase to find every page that mentions it.'
                : 'Try a different word. Scanned PDFs without selectable text can\'t be searched.');
    }
}
