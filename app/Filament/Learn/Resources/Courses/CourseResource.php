<?php

namespace App\Filament\Learn\Resources\Courses;

use App\Enums\CourseCategory;
use App\Filament\Learn\Resources\Courses\Pages\ListCourses;
use App\Filament\Learn\Resources\Courses\Pages\ReadMaterial;
use App\Filament\Learn\Resources\Courses\Pages\ViewCourse;
use App\Models\Course;
use App\Models\Material;
use App\Models\Topic;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
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
use Illuminate\Support\Facades\Auth;

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
        $userId = Auth::id();

        return parent::getEloquentQuery()
            ->visibleToStudents()
            ->with(['program', 'professors'])
            ->withCount([
                'publishedMaterials',
                'publishedMaterials as completed_materials_count' => fn (Builder $materials) => $materials->whereHas(
                    'progress',
                    fn (Builder $progress) => $progress->where('user_id', $userId)->whereNotNull('completed_at'),
                ),
            ]);
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
                TextColumn::make('progress')
                    ->label('My progress')
                    ->state(fn (Course $record): string => static::progressLabel($record))
                    ->badge()
                    ->color(fn (Course $record): string => static::progressColor($record)),
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
                        TextEntry::make('progress')
                            ->label('My progress')
                            ->state(fn (Course $record): string => static::progressLabel($record))
                            ->badge()
                            ->color(fn (Course $record): string => static::progressColor($record)),
                        TextEntry::make('professors.name')
                            ->label('Professors')
                            ->badge()
                            ->color('gray')
                            ->placeholder('To be announced'),
                    ]),
                Section::make('Lessons')
                    ->description('Topics and their readings, in order.')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('publishedTopics')
                            ->hiddenLabel()
                            ->placeholder('No lessons have been posted yet.')
                            ->schema([
                                TextEntry::make('title')
                                    ->hiddenLabel()
                                    ->weight('bold')
                                    ->size('lg'),
                                TextEntry::make('summary')
                                    ->hiddenLabel()
                                    ->color('gray')
                                    ->visible(fn (Topic $record): bool => filled($record->summary)),
                                RepeatableEntry::make('materials')
                                    ->hiddenLabel()
                                    ->placeholder('No PDFs in this topic yet.')
                                    ->grid(['default' => 1, 'sm' => 2, 'xl' => 3])
                                    ->schema([
                                        ImageEntry::make('thumbnail')
                                            ->hiddenLabel()
                                            ->state(fn (Material $record): ?string => $record->thumbnail_path !== null
                                                ? route('materials.thumbnail', $record)
                                                : null)
                                            ->imageHeight('10rem')
                                            ->url(fn (Material $record): string => static::readUrl($record)),
                                        TextEntry::make('title')
                                            ->hiddenLabel()
                                            ->weight('semibold')
                                            ->color('primary')
                                            ->icon(Heroicon::OutlinedDocumentText)
                                            ->url(fn (Material $record): string => static::readUrl($record)),
                                        TextEntry::make('details')
                                            ->hiddenLabel()
                                            ->color('gray')
                                            ->size('sm')
                                            ->state(fn (Material $record): string => $record->details()),
                                        TextEntry::make('status')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->state(fn (Material $record): ?string => static::materialStatus($record))
                                            ->color(fn (?string $state): string => $state === 'Done' ? 'success' : 'info')
                                            ->icon(fn (?string $state): Heroicon => $state === 'Done' ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedEye),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * "Done" / "Opened" / null for the signed-in user, from eager-loaded progress.
     */
    public static function materialStatus(Material $material): ?string
    {
        /** @var User $user */
        $user = Auth::user();
        $progress = $material->progressFor($user);

        return match (true) {
            $progress === null => null,
            $progress->isCompleted() => 'Done',
            default => 'Opened',
        };
    }

    public static function progressLabel(Course $course): string
    {
        $total = (int) $course->published_materials_count;

        if ($total === 0) {
            return 'No PDFs yet';
        }

        return "{$course->completed_materials_count} / {$total} done";
    }

    public static function progressColor(Course $course): string
    {
        $total = (int) $course->published_materials_count;
        $done = (int) $course->completed_materials_count;

        return match (true) {
            $total > 0 && $done >= $total => 'success',
            $done > 0 => 'warning',
            default => 'gray',
        };
    }

    public static function readUrl(Material $material, ?int $page = null): string
    {
        return static::getUrl('read', array_filter([
            'record' => $material->topic->course,
            'materialId' => $material->getKey(),
            'page' => $page,
        ]), panel: 'learn');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'view' => ViewCourse::route('/{record}'),
            'read' => ReadMaterial::route('/{record}/read/{materialId}'),
        ];
    }
}
