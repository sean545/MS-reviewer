<?php

namespace App\Filament\Learn\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Student home. The welcome banner widget carries the greeting, so the page has no heading of its own.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Home';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }
}
