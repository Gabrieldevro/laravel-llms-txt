<?php

namespace Gabrieldev\LlmsTxt;

use Gabrieldev\LlmsTxt\Commands\GenerateCommand;
use Gabrieldev\LlmsTxt\Http\LlmsTxtController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LlmsTxtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/llms-txt.php', 'llms-txt');

        $this->app->singleton(LlmsTxt::class, fn ($app) => new LlmsTxt($app['config'], $app['router']));
        $this->app->alias(LlmsTxt::class, 'llms-txt');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/llms-txt.php' => config_path('llms-txt.php')], 'llms-txt-config');

        if ($this->app->runningInConsole()) {
            $this->commands([GenerateCommand::class]);
        }

        if (config('llms-txt.serve.enabled', true)) {
            Route::middleware(config('llms-txt.serve.middleware', []))->group(function () {
                Route::get('llms.txt', [LlmsTxtController::class, 'index'])->name('llms-txt');
                Route::get('llms-full.txt', [LlmsTxtController::class, 'full'])->name('llms-txt.full');
            });
        }
    }
}
