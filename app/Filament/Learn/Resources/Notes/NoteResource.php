<?php

namespace App\Filament\Learn\Resources\Notes;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Filament\Learn\Resources\Courses\Pages\ReadMaterial;
use App\Filament\Learn\Resources\Notes\Pages\ManageNotes;
use App\Models\Note;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Every note the signed-in student has taken, across all courses. Notes are private to their author.
 */
class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'My notes';

    protected static ?string $modelLabel = 'note';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldSkipAuthorization = true;

    /**
     * @return Builder<Note>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->with('material.topic.course');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(ReadMaterial::noteFormSchema())->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('material.title')
                    ->label('Reading')
                    ->weight('semibold')
                    ->description(fn (Note $record): string => "{$record->material->topic->course->code} · {$record->material->topic->title}")
                    ->searchable(),
                TextColumn::make('page')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('body')
                    ->label('Note')
                    ->wrap()
                    ->limit(200)
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Saved')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('material.topic.course', 'code'),
            ])
            ->recordUrl(fn (Note $record): string => CourseResource::readUrl($record->material, $record->page))
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No notes yet')
            ->emptyStateDescription('Open any reading and use "Add note".');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNotes::route('/'),
        ];
    }
}
