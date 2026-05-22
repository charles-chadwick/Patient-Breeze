<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\SendPortalMessage;
use App\Enums\DiscussionType;
use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(): Response
    {
        /** @var Patient $patient */
        $patient = Auth::guard('portal')->user();

        $threads = $patient->discussions()
            ->where('type', DiscussionType::PortalMessage)
            ->with([
                'posts' => fn ($query) => $query->orderBy('created_at'),
                'posts.user:id,first_name,last_name',
                'posts.patient:id,first_name,last_name',
            ])
            ->latest()
            ->get()
            ->map(fn (Discussion $discussion) => [
                'id' => $discussion->id,
                'title' => $discussion->title,
                'created_at' => $discussion->created_at,
                'posts' => $discussion->posts->map(fn ($post) => [
                    'id' => $post->id,
                    'content' => $post->content,
                    'created_at' => $post->created_at,
                    'from_patient' => $post->patient_id !== null,
                    'author_name' => $post->patient_id
                        ? 'You'
                        : trim(($post->user?->first_name ?? 'Staff').' '.($post->user?->last_name ?? '')),
                ]),
            ]);

        return Inertia::render('Portal/Messages/Index', [
            'threads' => $threads,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Patient $patient */
        $patient = Auth::guard('portal')->user();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        SendPortalMessage::run($patient, $patient, [
            'title' => $data['title'] ?? 'Portal Message',
            'content' => $data['content'],
        ]);

        return redirect()->route('portal.messages.index');
    }

    public function reply(Request $request, Discussion $discussion): RedirectResponse
    {
        /** @var Patient $patient */
        $patient = Auth::guard('portal')->user();

        abort_unless(
            $discussion->discussionable_type === Patient::class
                && $discussion->discussionable_id === $patient->id,
            403
        );

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        SendPortalMessage::run($patient, $patient, ['content' => $data['content']], $discussion);

        return redirect()->route('portal.messages.index');
    }
}
