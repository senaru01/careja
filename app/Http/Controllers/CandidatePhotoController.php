<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidatePhotoController extends Controller
{
    /**
     * Stream a candidate's profile photo to viewers allowed to see the candidate.
     */
    public function show(Request $request, User $candidate): StreamedResponse
    {
        abort_unless($candidate->isCandidateVisibleTo($request->user()), 404);

        $path = $candidate->candidateProfile?->photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
