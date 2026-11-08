<?php

namespace App\Filament\Learn\Resources\Courses;

use App\Enums\CourseCategory;
use App\Filament\Learn\Resources\Courses\Pages\ListCourses;
use App\Filament\Learn\Resources\Courses\Pages\ViewCourse;
use App\Models\Course;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only course catalog for the student portal. Everyone signed in may browse
 * published courses, so the staff CoursePolicy is skipped here on purpose.
 */
class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $shouldSkipAuthorization = true;

    /**
     * @return Builder<Course>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visibleToStudents()
            ->with(['program', 'professors']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->defaultGroup(Group::make('program.name')->label('Program'))
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->weight('semibold')
                    ->color('primary'),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('units')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('program')
                    ->relationship('program', 'name', fn (Builder $query) => $query->where('is_published', true)),
                SelectFilter::make('category')
                    ->options(CourseCategory::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->recordUrl(fn (Course $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('About this course')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->placeholder('No description yet.'),
                    ]),
                Section::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('program.name')
                            ->label('Program'),
                        TextEntry::make('category')
                            ->badge(),
                        TextEntry::make('units'),
                        TextEntry::make('professors.name')
                            ->label('Professors')
                            ->badge()
                            ->color('gray')
                            ->placeholder('To be announced'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'view' => ViewCourse::route('/{record}'),
        ];
    }
}
