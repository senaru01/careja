<?php

namespace App\Http\Controllers;

use App\Models\CandidateDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Stream a candidate document to an authorized viewer.
     */
    public function show(CandidateDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document);

        return Storage::disk('local')->response($document->path, $document->original_name);
    }
}
