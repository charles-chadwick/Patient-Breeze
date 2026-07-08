<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PortalNotification;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_patients' => Patient::count(),
                'appointments_today' => Appointment::forDate(today())
                    ->withStatus(AppointmentStatus::Scheduled, AppointmentStatus::Confirmed)
                    ->count(),
                'pending_reviews' => Appointment::withStatus(AppointmentStatus::Scheduled)->count(),
                'portal_queue_unread' => PortalNotification::unread()->count(),
            ],
            'portal_queue' => PortalNotification::dashboardQueue(),
        ]);
    }
}
