<?php

namespace App\Notifications;

use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Patient;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * A patient directed a portal message to this staff user. Delivered to the
 * user's personal notification bell (stored in the database, pushed live over
 * the broadcast channel).
 */
class PortalMessageReceived extends Notification
{
    public function __construct(
        public Discussion $discussion,
        public DiscussionPost $post,
        public Patient $patient,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.portal_message.title', [
                'name' => "{$this->patient->first_name} {$this->patient->last_name}",
            ]),
            'body' => str($this->post->content)->limit(120)->toString(),
            'url' => route('patients.show', $this->patient).'?tab=discussions&discussion='.$this->discussion->id,
            'patient_id' => $this->patient->id,
            'discussion_id' => $this->discussion->id,
        ];
    }

    /**
     * The real-time payload pushed to the user's notification channel.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
