<?php
/*
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user(); // Assuming standard or temporary local auth for now

        // Check user type (e.g., if UserTypeId represents staff vs patient)
        if ($user->UserTypeId === 2) { // Assuming staff type ID
            $data = [
                'upcomingAppointments' => \App\Models\Appointment::with(['patient', 'appointmentType'])
                    ->where('ScheduleDateTime', '>=', now())
                    ->orderBy('ScheduleDateTime', 'asc')
                    ->take(5)
                    ->get(),
                'lowStockItems' => \App\Models\ItemInventory::where('Quantity', '<=', 10)->get(),
                'totalAppointmentsToday' => \App\Models\Appointment::whereDate('ScheduleDateTime', today())->count(),
            ];
        } else {
            // Patient view
            $data = [
                'myAppointments' => \App\Models\Appointment::where('Patient_AppUserId', $user->Id)
                    ->orderBy('ScheduleDateTime', 'desc')
                    ->take(5)
                    ->get(),
            ];
        }

        return view('dashboard.index', compact('data'));
    }
}
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user(); 

        // Temporary toggle for UI testing (true = staff view, false = patient view)
        $isStaff = true; 

        if ($isStaff) { 
            $data = [
                'upcomingAppointments' => \App\Models\Appointment::where('SCHEDULED_AT', '>=', now())
                ->orderBy('SCHEDULED_AT', 'asc')
                ->take(5)
                ->get(),
            'lowStockItems' => \App\Models\Inventory::where('ITEM_QUANTITY', '<=', 10)->get(),
            'totalAppointmentsToday' => \App\Models\Appointment::whereDate('SCHEDULED_AT', today())->count(),
            'totalAppointments' => \App\Models\Appointment::count(),
            ];
        } else {
            // Patient view
            $data = [
                'myAppointments' => \App\Models\Appointment::where('PATIENT_NAME', $user->Name ?? '') // Or use the correct patient identifier column
                    ->orderBy('SCHEDULED_AT', 'desc')
                    ->take(5)
                    ->get(),
            ];
        }
        return view('dashboard.index', compact('data'));
    }
}