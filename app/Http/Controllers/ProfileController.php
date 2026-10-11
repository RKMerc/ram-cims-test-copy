<?php

namespace App\Http\Controllers;

use App\Models\LinkedIdentity;
use App\Support\ClinicAccess;

class ProfileController extends Controller
{
    public function show(ClinicAccess $access)
    {
        $account = $access->account();
        abort_unless($account, 404);
        $microsoft = LinkedIdentity::query()
            ->where('user_id', auth()->id())
            ->where('provider', 'microsoft')
            ->first();

        return view('profile.show', compact('account', 'microsoft'));
    }
}
