<?php

namespace App\Filament\Resources\Announcements\Tables;

use App\Models\Announcement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('')
                    ->icon(fn (bool $state): ?string => $state ? 'heroicon-s-bookmark' : null)
                    ->color('primary'),
                TextColumn::make('title')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (Announcement $record): string => str($record->body)->limit(80)->toString()),
                TextColumn::make('audience')
                    ->state(fn (Announcement $record): string => $record->audienceLabel())
                    ->badge()
                    ->color(fn (Announcement $record): string => $record->course_id ? 'gray' : 'primary'),
                TextColumn::make('published_at')
                    ->label('Publish at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Draft')
                    ->description(fn (Announcement $record): ?string => $record->published_at?->isFuture() ? 'Scheduled' : null),
                TextColumn::make('author.name')
                    ->label('By')
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
