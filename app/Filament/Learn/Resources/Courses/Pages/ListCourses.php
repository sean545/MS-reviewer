<?php

namespace App\Filament\Learn\Resources\Courses\Pages;

use App\Filament\Learn\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;
}
