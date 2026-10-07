<?php

namespace App\Providers;

use App\Support\ClinicAccess;
use App\Support\DeveloperMode;
use App\Support\DutyBoard;
use Carbon\Carbon;
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
            $view->with('developerMode', DeveloperMode::enabled());
        });

        View::composer('partials.duty-board', function ($view) {
            $board = app(DutyBoard::class);
            $practitioners = $board->practitioners();
            $names = array_column($practitioners, 'name');
            $selected = request('practitioner');

            if (! is_string($selected) || ! in_array($selected, $names, true)) {
                $selected = $names[0] ?? null;
            }

            try {
                $date = Carbon::parse(request('duty_date', today()->toDateString()))->toDateString();
            } catch (\Throwable) {
                $date = today()->toDateString();
            }

            $person = collect($practitioners)->firstWhere('name', $selected);

            $view->with([
                'dutyPractitioners' => $practitioners,
                'dutyPractitioner' => $selected,
                'dutyPractitionerLabel' => $person['label'] ?? null,
                'dutyDate' => $date,
                'dutySlots' => $selected ? $board->slots($selected, $date) : [],
            ]);
        });
    }
}