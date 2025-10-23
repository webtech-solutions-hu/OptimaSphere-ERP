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
        Gate::policy(Session::class, SessionPolicy::class);
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(CompletedJob::class, CompletedJobPolicy::class);
        Gate::policy(FailedJob::class, FailedJobPolicy::class);

        // Register activity log event subscriber
        Event::subscribe(LogUserActivity::class);
    }
}
