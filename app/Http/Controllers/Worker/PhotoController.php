<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    /**
     * Upload or replace the candidate's profile photo (stored on the private disk).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validateWithBag('photo', [
            'photo' => [
                'required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp',
                'max:'.config('careja.max_upload_kb'),
                'dimensions:min_width=200,min_height=200',
            ],
        ], [
            'photo.dimensions' => 'Foto minimal berukuran 200 x 200 piksel.',
        ], ['photo' => 'foto']);

        $profile = $request->user()->candidateProfile()->firstOrNew();
        $oldPath = $profile->photo_path;

        $profile->forceFill(['photo_path' => $request->file('photo')->store('photos', 'local')])->save();

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('status', 'Foto profil diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $profile = $request->user()->candidateProfile;

        if ($profile?->photo_path) {
            Storage::disk('local')->delete($profile->photo_path);
            $profile->forceFill(['photo_path' => null])->save();
        }

        return back()->with('status', 'Foto profil dihapus.');
    }
}
