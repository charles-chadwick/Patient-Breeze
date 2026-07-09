<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        /** @var Patient $patient */
        $patient = Auth::guard('portal')->user();

        return Inertia::render('Portal/Dashboard', [
            'patient' => $patient->only(['id', 'first_name', 'last_name', 'mrn', 'date_of_birth', 'blood_type', 'gender_identity']),
            'appointments' => $patient->upcomingAppointments(),
            'appointment_requests' => $patient->portalAppointmentRequests(),
            'discussions' => $patient->recentDiscussions(),
            'documents' => $patient->portalDocuments(),
            'document_type_options' => DocumentType::values(),
        ]);
    }
}
