<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\StoreDocumentRequest;
use App\Models\CandidateDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Store an uploaded document on the private disk.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        $request->user()->documents()->create([
            'type' => $request->validated('type'),
            'path' => $file->store('documents', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('status', 'Dokumen berhasil diunggah.');
    }

    public function destroy(CandidateDocument $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('status', 'Dokumen dihapus.');
    }
}
