<?php

namespace App\Providers;

use App\Helpers\AssetHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AssetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Custom Blade directive for relative assets
        Blade::directive('relativeVite', function ($expression) {
            return "<?php echo App\Helpers\AssetHelper::viteAsset($expression); ?>";
        });
        
        Blade::directive('relativeViteCss', function ($expression) {
            return "<?php echo App\Helpers\AssetHelper::viteCss($expression); ?>";
        });
    }
}
