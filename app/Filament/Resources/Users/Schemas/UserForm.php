<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->required(fn (Page $livewire): bool => $livewire instanceof CreateRecord)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (Page $livewire): ?string => $livewire instanceof CreateRecord ? null : 'Leave blank to keep the current password.'),
                    ]),
                Section::make('Role')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Radio::make('role')
                            ->hiddenLabel()
                            ->options(UserRole::class)
                            ->default(UserRole::Student)
                            ->required()
                            ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false)
                            ->helperText(fn (?User $record): ?string => $record?->is(Auth::user())
                                ? 'You can\'t change your own role, so the site always keeps an admin.'
                                : null),
                    ]),
            ]);
    }
}
