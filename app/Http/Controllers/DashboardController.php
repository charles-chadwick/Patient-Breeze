<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_patients' => Patient::count(),
                'appointments_today' => Appointment::whereDate('date', today())
                    ->whereIn('status', [
                        AppointmentStatus::Scheduled,
                        AppointmentStatus::Confirmed,
                    ])
                    ->count(),
                'pending_reviews' => Appointment::where('status', AppointmentStatus::Scheduled)
                    ->count(),
            ],
        ]);
    }
}
