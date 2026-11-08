<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Student portal: browse courses, read lesson PDFs, track progress, take notes and search readings. Anyone may register as a student.
 */
class LearnPanelProvider extends PanelProvider
{
    /**
     * "Mint Clean" palette shared by both panels.
     *
     * @return array<string, array<int|string, string>>
     */
    public static function colors(): array
    {
        return [
            'primary' => Color::hex('#12a26a'),
            'success' => Color::Emerald,
            'gray' => Color::Gray,
        ];
    }

    /**
     * Applies the shared look (palette, font, compiled theme) to a panel.
     */
    public static function applyTheme(Panel $panel): Panel
    {
        return $panel
            ->viteTheme('resources/css/filament/learn/theme.css')
            ->font('Plus Jakarta Sans')
            ->colors(self::colors())
            ->favicon(asset('images/logo.svg'));
    }

    public function panel(Panel $panel): Panel
    {
        return self::applyTheme($panel)
            ->id('learn')
            ->path('learn')
            ->login()
            ->profile(isSimple: false)
            ->registration()
            ->brandName(config('app.name'))
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/Learn/Resources'), for: 'App\Filament\Learn\Resources')
            ->discoverPages(in: app_path('Filament/Learn/Pages'), for: 'App\Filament\Learn\Pages')
            ->discoverWidgets(in: app_path('Filament/Learn/Widgets'), for: 'App\Filament\Learn\Widgets')
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
