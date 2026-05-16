<?php

namespace App\Http\Controllers\Portal;

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

        $appointments = $patient->appointments()
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(5)
            ->with('users')
            ->get();

        $discussions = $patient->discussions()
            ->latest()
            ->limit(3)
            ->get();

        $documents = $patient->getMedia('*')
            ->filter(fn ($media) => $media->collection_name !== 'avatar')
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'created_at' => $media->created_at->toDateString(),
            ])
            ->values();

        return Inertia::render('Portal/Dashboard', [
            'patient' => $patient->only(['id', 'first_name', 'last_name', 'mrn', 'date_of_birth', 'blood_type', 'gender_identity']),
            'appointments' => $appointments,
            'discussions' => $discussions,
            'documents' => $documents,
        ]);
    }
}
