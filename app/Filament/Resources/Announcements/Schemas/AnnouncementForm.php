<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('body')
                            ->label('Message')
                            ->required()
                            ->rows(6)
                            ->maxLength(5000),
                    ]),
                Section::make('Audience & timing')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Select::make('course_id')
                            ->label('Course')
                            ->relationship(
                                'course',
                                'code',
                                fn (Builder $query) => $query
                                    ->when(! $user->isAdmin(), fn (Builder $courses) => $courses->whereHas(
                                        'professors',
                                        fn (Builder $professors) => $professors->whereKey($user->getKey()),
                                    ))
                                    ->orderBy('code'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->displayName())
                            ->searchable()
                            ->preload()
                            ->required(! $user->isAdmin())
                            ->placeholder('All students')
                            ->helperText($user->isAdmin() ? 'Leave empty to announce to everyone.' : null),
                        DateTimePicker::make('published_at')
                            ->label('Publish at')
                            ->default(now())
                            ->seconds(false)
                            ->helperText('Set a future time to schedule it. Clear it to keep as a draft.'),
                        Toggle::make('is_pinned')
                            ->label('Pin to top'),
                    ]),
            ]);
    }
}
