<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\SendPortalMessage;
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

        return Inertia::render('Portal/Messages/Index', [
            'threads' => $patient->portalMessageThreads(),
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

        return redirect()->route('portal.messages.index')->with('success', __('flash.portal_messages.sent'));
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
