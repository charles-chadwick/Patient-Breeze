<?php

namespace App\Http\Controllers;

use App\Actions\AttachDocumentAction;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, Patient $patient, AttachDocumentAction $attachDocument): RedirectResponse
    {
        $validated = $request->validated();

        $attachDocument->execute($patient, $validated, $request->file('file'), Auth::user());

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.documents.uploaded'));
    }

    public function destroy(Patient $patient, Document $document): RedirectResponse
    {
        $document->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', __('flash.documents.deleted'));
    }

    public function download(Patient $patient, Document $document): BinaryFileResponse
    {
        $media = $document->getFirstMedia('file');

        abort_if($media === null, 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
