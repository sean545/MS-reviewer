<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Enums\CourseCategory;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        $isAdmin = fn (): bool => static::currentUserIsAdmin();
        $isNotAdmin = fn (): bool => ! static::currentUserIsAdmin();
        $syncSlug = fn (Get $get, Set $set) => $set('slug', Str::slug($get('code').' '.$get('title')));

        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Course')
                        ->columns(2)
                        ->schema([
                            Select::make('program_id')
                                ->relationship('program', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled($isNotAdmin)
                                ->columnSpanFull(),
                            TextInput::make('code')
                                ->placeholder('e.g. DS 201')
                                ->required()
                                ->maxLength(30)
                                ->disabled($isNotAdmin)
                                ->live(onBlur: true)
                                ->afterStateUpdated($syncSlug),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->disabled($isNotAdmin)
                                ->live(onBlur: true)
                                ->afterStateUpdated($syncSlug),
                            Textarea::make('description')
                                ->helperText('What the course covers. Shown to students on the course page.')
                                ->rows(5)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make('Details')
                        ->schema([
                            Select::make('category')
                                ->options(CourseCategory::class)
                                ->default(CourseCategory::Core)
                                ->required()
                                ->disabled($isNotAdmin),
                            TextInput::make('units')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(12)
                                ->default(3)
                                ->required()
                                ->disabled($isNotAdmin),
                            TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->disabled($isNotAdmin),
                            Toggle::make('is_published')
                                ->label('Visible to students')
                                ->default(true)
                                ->disabled($isNotAdmin),
                        ]),
                    Section::make('Professors')
                        ->description('They can edit this course and, from phase 3, upload its lesson PDFs.')
                        ->visible($isAdmin)
                        ->schema([
                            Select::make('professors')
                                ->hiddenLabel()
                                ->relationship(
                                    'professors',
                                    'name',
                                    fn (Builder $query) => $query->where('role', UserRole::Professor),
                                )
                                ->multiple()
                                ->searchable()
                                ->preload(),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    protected static function currentUserIsAdmin(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }
}
