<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CourseCategory: string implements HasColor, HasLabel
{
    case Foundation = 'foundation';
    case Core = 'core';
    case Elective = 'elective';
    case Thesis = 'thesis';

    public function getLabel(): string
    {
        return match ($this) {
            self::Foundation => 'Foundation',
            self::Core => 'Core',
            self::Elective => 'Elective',
            self::Thesis => 'Thesis / Special Problem',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Foundation => 'info',
            self::Core => 'primary',
            self::Elective => 'warning',
            self::Thesis => 'gray',
        };
    }
}
