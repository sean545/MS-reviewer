<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Admin = 'admin';
    case Professor = 'professor';
    case Student = 'student';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Professor => 'Professor',
            self::Student => 'Student',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Admin => 'Full access: courses, materials, and user accounts and roles.',
            self::Professor => 'Manages the courses they handle and uploads lesson PDFs.',
            self::Student => 'Reads and reviews course materials in the student portal.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Professor => 'primary',
            self::Student => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Admin => 'heroicon-o-shield-check',
            self::Professor => 'heroicon-o-academic-cap',
            self::Student => 'heroicon-o-user',
        };
    }

    /**
     * Whether this role may sign in to the admin panel.
     */
    public function isStaff(): bool
    {
        return $this !== self::Student;
    }
}
