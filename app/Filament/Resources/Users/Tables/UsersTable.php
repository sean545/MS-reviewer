<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(UserRole::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(fn ($records) => $records->reject(fn (User $user): bool => $user->is(Auth::user()))->each->delete()),
                ]),
            ]);
    }
}
