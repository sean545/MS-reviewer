<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Student portal: browse courses and review lesson PDFs. Anyone may register as a student.
 */
class LearnPanelProvider extends PanelProvider
{
    /**
     * Green palette shared by both panels.
     *
     * @return array<string, array<int|string, string>>
     */
    public static function colors(): array
    {
        return [
            'primary' => Color::hex('#1f7a4d'),
            'success' => Color::Emerald,
            'gray' => Color::Stone,
        ];
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('learn')
            ->path('learn')
            ->login()
            ->registration()
            ->brandName(config('app.name'))
            ->colors(self::colors())
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/Learn/Resources'), for: 'App\Filament\Learn\Resources')
            ->discoverPages(in: app_path('Filament/Learn/Pages'), for: 'App\Filament\Learn\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
