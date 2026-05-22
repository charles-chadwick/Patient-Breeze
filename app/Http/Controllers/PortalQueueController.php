<?php

namespace App\Http\Controllers;

use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortalQueueController extends Controller
{
    public function index(): Response
    {
        $notifications = PortalNotification::with('patient:id,first_name,last_name,mrn')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (PortalNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'url' => $notification->url,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'patient' => $notification->patient
                    ? $notification->patient->only(['id', 'first_name', 'last_name', 'mrn'])
                    : null,
            ]);

        $unread_count = PortalNotification::unread()->count();

        return Inertia::render('PortalQueue/Index', [
            'notifications' => $notifications,
            'unread_count' => $unread_count,
        ]);
    }

    public function markRead(PortalNotification $notification): RedirectResponse
    {
        $notification->markAsRead();

        return back();
    }
}
