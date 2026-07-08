<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Document;
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
            ->get(['id', 'date', 'start_time', 'end_time', 'reason', 'status']);

        $discussions = $patient->discussions()
            ->latest()
            ->limit(3)
            ->get(['id', 'title', 'status', 'created_at']);

        $documents = $patient->documents()
            ->with(['media', 'uploader'])
            ->latest()
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'type_label' => $document->type->label(),
                'name' => $document->name,
                'document_date' => $document->document_date?->toDateString(),
                'notes' => $document->notes,
                'created_at' => $document->created_at->toDateString(),
                'download_url' => route('portal.documents.download', $document->id),
                'can_delete' => $document->uploader_type === Patient::class && $document->uploader_id === $patient->id,
            ]);

        return Inertia::render('Portal/Dashboard', [
            'patient' => $patient->only(['id', 'first_name', 'last_name', 'mrn', 'date_of_birth', 'blood_type', 'gender_identity']),
            'appointments' => $appointments,
            'discussions' => $discussions,
            'documents' => $documents,
            'document_type_options' => DocumentType::values(),
        ]);
    }
}
