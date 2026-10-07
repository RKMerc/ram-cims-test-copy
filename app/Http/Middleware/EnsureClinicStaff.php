<?php

namespace App\Http\Middleware;

use App\Support\ClinicAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicStaff
{
    public function __construct(private ClinicAccess $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        if (! $this->access->isStaff()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'That area is limited to clinic staff.');
        }

        return $next($request);
    }
}
