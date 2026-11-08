<?php

namespace App\Filament\Learn\Resources\Notes\Pages;

use App\Filament\Learn\Resources\Notes\NoteResource;
use Filament\Resources\Pages\ManageRecords;

class ManageNotes extends ManageRecords
{
    protected static string $resource = NoteResource::class;
}
