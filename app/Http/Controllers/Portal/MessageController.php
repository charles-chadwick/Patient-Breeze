<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\SendPortalMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreMessageRequest;
use App\Models\Discussion;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

        return Inertia::render('Portal/Messages/Index', [
            'threads' => $patient->portalMessageThreads(),
        ]);
    }

    public function store(StoreMessageRequest $request): RedirectResponse
    {
        /** @var Patient $patient */
        $patient = Auth::guard('portal')->user();

        $data = $request->validated();

        SendPortalMessage::run($patient, $patient, [
            'title' => $data['title'] ?? 'Portal Message',
            'content' => $data['content'],
            'recipient_ids' => $data['recipient_ids'] ?? [],
        ]);

        return redirect()->route('portal.messages.index')->with('success', __('flash.portal_messages.sent'));
    }

    /**
     * Search staff users who have opted in to receiving directed portal
     * messages, shaped for the recipient picker.
     */
    public function recipientSearch(Request $request): JsonResponse
    {
        $users = User::receivingPortalMessages()->forPicker($request->string('search')->toString());

        return response()->json(['users' => $users]);
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
