<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Enums\CourseCategory;
use App\Models\Course;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->defaultGroup(Group::make('program.name')->label('Program'))
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('units')
                    ->alignCenter(),
                TextColumn::make('professors.name')
                    ->label('Professors')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Unassigned'),
                IconColumn::make('is_published')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('program')
                    ->relationship('program', 'name'),
                SelectFilter::make('category')
                    ->options(CourseCategory::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->recordTitle(fn (Course $record): string => $record->displayName())
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
