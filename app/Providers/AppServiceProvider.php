<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.force_https', false)) {
            URL::forceScheme('https');
        }
        
        // Handle custom domain asset URLs
        // If accessed via custom domain, use that domain for assets too
        if (request()->getHost() !== 'task-it-9b20e77d6638.herokuapp.com') {
            $currentUrl = request()->getScheme() . '://' . request()->getHost();
            URL::forceRootUrl($currentUrl);
            config(['app.url' => $currentUrl]);
        }
    }
}
