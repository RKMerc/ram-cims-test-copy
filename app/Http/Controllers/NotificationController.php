<?php

namespace App\Http\Controllers;

use App\Models\ClinicNotification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = ClinicNotification::query()->latest()->get();
        $readIds = NotificationRead::query()
            ->where('user_id', $request->user()->id)
            ->pluck('clinic_notification_id');

        return view('notifications.index', compact('notifications', 'readIds'));
    }

    public function read(Request $request, ClinicNotification $notification)
    {
        NotificationRead::query()->updateOrCreate(
            [
                'clinic_notification_id' => $notification->id,
                'user_id' => $request->user()->id,
            ],
            ['read_at' => now()]
        );

        return back()->with('success', 'Notification marked as read.');
    }
}
