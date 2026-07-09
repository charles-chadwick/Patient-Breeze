<?php

namespace App\Actions\Portal;

use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionType;
use App\Events\PortalNotificationCreated;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Patient;
use App\Models\PortalNotification;
use App\Models\User;
use App\Notifications\PortalMessageReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;

class SendPortalMessage
{
    use AsAction;

    /**
     * Add a post to a Portal Message discussion. If $discussion is null, a
     * new discussion is created for the given patient. Any recipient_ids are
     * attached to a newly created discussion as (non-initiator) participants.
     *
     * @param  array{title?: ?string, content: string, recipient_ids?: list<int>}  $data
     */
    public function handle(Patient $patient, Patient|User $author, array $data, ?Discussion $discussion = null): DiscussionPost
    {
        return DB::transaction(function () use ($patient, $author, $data, $discussion) {
            $discussion ??= $this->createDiscussion($patient, $author, $data['title'] ?? 'Portal Message', $data['recipient_ids'] ?? []);

            $post = $discussion->posts()->create([
                'user_id' => $author instanceof User ? $author->id : null,
                'patient_id' => $author instanceof Patient ? $author->id : null,
                'status' => DiscussionPostStatus::Published,
                'content' => $data['content'],
            ]);

            if ($author instanceof Patient) {
                $notification = PortalNotification::create([
                    'type' => 'portal.message.received',
                    'notifiable_type' => DiscussionPost::class,
                    'notifiable_id' => $post->id,
                    'patient_id' => $patient->id,
                    'title' => $discussion->wasRecentlyCreated
                        ? "{$patient->first_name} {$patient->last_name} sent a new message"
                        : "{$patient->first_name} {$patient->last_name} replied to a message",
                    'body' => str($post->content)->limit(140),
                    'url' => route('patients.show', $patient).'?tab=discussions&discussion='.$discussion->id,
                ]);

                PortalNotificationCreated::dispatch($notification);

                $this->notifyDirectedRecipients($patient, $discussion, $post, $data['recipient_ids'] ?? []);
            }

            return $post;
        });
    }

    /**
     * @param  list<int>  $recipientIds
     */
    private function createDiscussion(Patient $patient, Patient|User $author, string $title, array $recipientIds = []): Discussion
    {
        /** @var Discussion $discussion */
        $discussion = $patient->discussions()->create([
            'type' => DiscussionType::PortalMessage,
            'title' => $title,
            'status' => 'Open',
        ]);

        $discussion->participants()->create([
            'participantable_type' => $author::class,
            'participantable_id' => $author->id,
            'is_initiator' => true,
        ]);

        if (! ($author instanceof Patient)) {
            $discussion->participants()->create([
                'participantable_type' => Patient::class,
                'participantable_id' => $patient->id,
                'is_initiator' => false,
            ]);
        }

        foreach (array_unique($recipientIds) as $recipient_id) {
            $discussion->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $recipient_id,
                'is_initiator' => false,
            ]);
        }

        return $discussion;
    }

    /**
     * Send a personal (bell) notification to each staff user the patient
     * directed this new message to.
     *
     * @param  list<int>  $recipientIds
     */
    private function notifyDirectedRecipients(Patient $patient, Discussion $discussion, DiscussionPost $post, array $recipientIds): void
    {
        if ($recipientIds === []) {
            return;
        }

        $recipients = User::whereKey(array_unique($recipientIds))->get();

        Notification::send($recipients, new PortalMessageReceived($discussion, $post, $patient));
    }
}
