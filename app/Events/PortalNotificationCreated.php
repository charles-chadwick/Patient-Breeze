<?php

namespace App\Events;

use App\Models\PortalNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PortalNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PortalNotification $notification) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('portal-queue'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PortalNotificationCreated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->notification->loadMissing('patient:id,first_name,last_name,mrn');

        return [
            'notification' => [
                'id' => $this->notification->id,
                'type' => $this->notification->type,
                'title' => $this->notification->title,
                'body' => $this->notification->body,
                'url' => $this->notification->url,
                'read_at' => $this->notification->read_at,
                'created_at' => $this->notification->created_at,
                'patient' => $this->notification->patient
                    ? [
                        'id' => $this->notification->patient->id,
                        'first_name' => $this->notification->patient->first_name,
                        'last_name' => $this->notification->patient->last_name,
                        'mrn' => $this->notification->patient->mrn,
                    ]
                    : null,
            ],
        ];
    }
}
