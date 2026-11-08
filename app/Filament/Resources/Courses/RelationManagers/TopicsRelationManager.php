<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\Material;
use App\Models\Topic;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Topics of a course and the lesson PDFs inside each one. Used by admins and the course's professors.
 */
class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    protected static ?string $title = 'Topics & PDFs';

    protected static ?string $recordTitleAttribute = 'title';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('title')
                    ->placeholder('e.g. Week 3 · Linear Regression')
                    ->required()
                    ->maxLength(255),
                Textarea::make('summary')
                    ->helperText('Optional. A short note on what to focus on when reviewing.')
                    ->rows(2),
                Toggle::make('is_published')
                    ->label('Visible to students')
                    ->default(true),
                Repeater::make('materials')
                    ->label('Lesson PDFs')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->defaultItems(0)
                    ->collapsible()
                    ->addActionLabel('Add PDF')
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                        ...$data,
                        'uploaded_by' => Auth::id(),
                    ])
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('PDF file')
                            ->disk(Material::DISK)
                            ->directory('materials')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(102400)
                            ->storeFileNamesIn('original_name')
                            ->required()
                            ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                                $file = Arr::first(Arr::wrap($state));

                                if (blank($get('title')) && $file instanceof TemporaryUploadedFile) {
                                    $set('title', Str::of($file->getClientOriginalName())->beforeLast('.')->replace(['_', '-'], ' ')->squish()->title()->toString());
                                }
                            }),
                        TextInput::make('title')
                            ->helperText('Filled in from the file name; edit if needed.')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->weight('semibold')
                    ->description(fn (Topic $record): ?string => Str::limit((string) $record->summary, 90) ?: null)
                    ->searchable(),
                TextColumn::make('materials_count')
                    ->counts('materials')
                    ->label('PDFs')
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('is_published')
                    ->label('Visible'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add topic')
                    ->icon(Heroicon::OutlinedPlus)
                    ->slideOver(),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No topics yet')
            ->emptyStateDescription('Add a topic, then attach its reading PDFs.');
    }
}
