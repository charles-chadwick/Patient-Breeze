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
        $portal_queue = PortalNotification::with('patient:id,first_name,last_name,mrn')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (PortalNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'patient' => $notification->patient
                    ? $notification->patient->only(['id', 'first_name', 'last_name', 'mrn'])
                    : null,
            ]);

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
                'portal_queue_unread' => PortalNotification::unread()->count(),
            ],
            'portal_queue' => $portal_queue,
        ]);
    }
}
