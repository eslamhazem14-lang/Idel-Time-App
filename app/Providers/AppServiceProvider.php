<?php

namespace App\Providers;

use App\Services\SettingsService;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(TaskTypeRegistry::class, fn () => new TaskTypeRegistry(config('tasktypes')));
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->uncompromised()
            : Password::min(8));

        Paginator::defaultView('components.pagination');

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $key = fn (Request $request) => $request->user()?->id ?: $request->ip();

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('claims', fn (Request $request) => Limit::perMinute(10)->by($key($request)));
        RateLimiter::for('submissions', fn (Request $request) => Limit::perMinute(10)->by($key($request)));
        RateLimiter::for('drafts', fn (Request $request) => Limit::perMinute(30)->by($key($request)));
        RateLimiter::for('withdrawals', fn (Request $request) => Limit::perHour(5)->by($key($request)));
        RateLimiter::for('reports', fn (Request $request) => Limit::perHour(20)->by($key($request)));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($key($request)));
    }
}
