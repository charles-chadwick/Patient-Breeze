<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortalQueueController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PortalQueue/Index', [
            ...PortalNotification::queue(),
            'appointment_requests' => AppointmentRequest::pendingQueue(),
        ]);
    }

    public function markRead(PortalNotification $notification): RedirectResponse
    {
        $notification->markAsRead();

        return back();
    }
}
