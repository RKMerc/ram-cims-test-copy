<?php

namespace App\Providers;

use App\Support\ClinicAccess;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClinicAccess::class);
    }

    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', Provider::class);
        });

        View::composer('*', function ($view) {
            $access = app(ClinicAccess::class);
            $view->with('clinicAccount', $access->account());
            $view->with('isClinicStaff', $access->isStaff());
        });
    }
}