<?php

namespace App\Http\Controllers;

use App\Support\ClinicAccess;

class ProfileController extends Controller
{
    public function show(ClinicAccess $access)
    {
        $account = $access->account();
        abort_unless($account, 404);

        return view('profile.show', compact('account'));
    }
}
