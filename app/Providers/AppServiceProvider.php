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
                $monday = Carbon::parse(request('week', today()->toDateString()))->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable) {
                $monday = today()->copy()->startOfWeek(Carbon::MONDAY);
            }

            $person = collect($practitioners)->firstWhere('name', $selected);

            $view->with([
                'dutyPractitioners' => $practitioners,
                'dutyPractitioner' => $selected,
                'dutyPractitionerLabel' => $person['label'] ?? null,
                'weekStart' => $monday->toDateString(),
                'weekLabel' => $monday->format('M j').' – '.$monday->copy()->addDays(4)->format('M j, Y'),
                'previousWeek' => $monday->copy()->subWeek()->toDateString(),
                'nextWeek' => $monday->copy()->addWeek()->toDateString(),
                'dutyGrid' => $selected ? $board->week($selected, $monday) : ['days' => [], 'rows' => []],
            ]);
        });
    }
}