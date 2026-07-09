<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\RequestAppointment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreAppointmentRequestRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentRequestController extends Controller
{
    public function store(StoreAppointmentRequestRequest $request): RedirectResponse
    {
        /** @var Patient $patient */
        $patient = Auth::guard('portal')->user();

        RequestAppointment::run($patient, $request->validated());

        return redirect()->route('portal.dashboard')
            ->with('success', __('flash.appointment_requests.requested'));
    }

    /**
     * Search staff providers for the appointment request picker.
     */
    public function providerSearch(Request $request): JsonResponse
    {
        $users = User::staff()->forPicker($request->string('search')->toString());

        return response()->json(['users' => $users]);
    }
}
