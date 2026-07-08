<?php

namespace App\Http\Controllers\Portal;

use App\Actions\AttachDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, AttachDocumentAction $attachDocument): RedirectResponse
    {
        $patient = $this->patient();

        $attachDocument->execute($patient, $request->validated(), $request->file('file'), $patient);

        return redirect()->route('portal.dashboard')
            ->with('success', __('flash.documents.uploaded'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $patient = $this->patient();

        $this->authorizeOwnership($document, $patient);

        abort_unless(
            $document->uploader_type === Patient::class && $document->uploader_id === $patient->id,
            403
        );

        $document->delete();

        return redirect()->route('portal.dashboard')
            ->with('success', __('flash.documents.deleted'));
    }

    public function download(Document $document): BinaryFileResponse
    {
        $patient = $this->patient();

        $this->authorizeOwnership($document, $patient);

        $media = $document->getFirstMedia('file');

        abort_if($media === null, 404);

        return response()->download($media->getPath(), $media->file_name);
    }

    private function patient(): Patient
    {
        /** @var Patient $patient */
        $patient = Auth::guard('portal')->user();

        return $patient;
    }

    private function authorizeOwnership(Document $document, Patient $patient): void
    {
        abort_unless(
            $document->documentable_type === Patient::class && $document->documentable_id === $patient->id,
            403
        );
    }
}
