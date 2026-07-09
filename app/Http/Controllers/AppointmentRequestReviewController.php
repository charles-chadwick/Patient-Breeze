<?php

namespace App\Http\Controllers;

use App\Actions\ApproveAppointmentRequest;
use App\Enums\AppointmentRequestStatus;
use App\Models\AppointmentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AppointmentRequestReviewController extends Controller
{
    public function __construct(private ApproveAppointmentRequest $approveAction) {}

    public function approve(AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $this->authorize('review', $appointmentRequest);

        abort_unless($appointmentRequest->isPending(), 422);

        /** @var User $reviewer */
        $reviewer = Auth::user();

        $this->approveAction->execute($appointmentRequest, $reviewer);

        return back()->with('success', __('flash.appointment_requests.approved'));
    }

    public function decline(AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $this->authorize('review', $appointmentRequest);

        abort_unless($appointmentRequest->isPending(), 422);

        /** @var User $reviewer */
        $reviewer = Auth::user();

        $appointmentRequest->update([
            'status' => AppointmentRequestStatus::Declined,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('flash.appointment_requests.declined'));
    }
}
