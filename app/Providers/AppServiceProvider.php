<?php

namespace App\Providers;

use App\Listeners\LogUserActivity;
use App\Models\CompletedJob;
use App\Models\FailedJob;
use App\Models\Job;
use App\Models\Session;
use App\Policies\CompletedJobPolicy;
use App\Policies\FailedJobPolicy;
use App\Policies\JobPolicy;
use App\Policies\SessionPolicy;
use Carbon\Carbon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Set default date format for Carbon
        Carbon::serializeUsing(function ($carbon) {
            return $carbon->format('Y-m-d H:i:s');
        });

        Gate::policy(Session::class, SessionPolicy::class);
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(CompletedJob::class, CompletedJobPolicy::class);
        Gate::policy(FailedJob::class, FailedJobPolicy::class);

        // Register activity log event subscriber
        Event::subscribe(LogUserActivity::class);

        // Register footer render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            fn (): string => view('filament.footer')->render(),
        );
    }
}
