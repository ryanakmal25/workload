<?php

namespace App\Providers;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        FilamentColor::register([
            'indigo'  => Color::Indigo,
            'sky'     => Color::Sky,
            'fuchsia' => Color::Fuchsia,
            'rose'    => Color::Rose,
            'zinc'    => Color::Zinc,
            'pink'    => Color::Pink,
            'cyan'    => Color::Cyan,
            'teal'    => Color::Teal,

        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
