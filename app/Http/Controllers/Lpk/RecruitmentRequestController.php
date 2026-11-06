<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Recruitment requests companies sent to this LPK, for it to follow up
 * with a test session.
 */
class RecruitmentRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('lpk.recruitments.index', [
            'requests' => RecruitmentRequest::query()
                ->sentTo($request->user()->organization)
                ->with(['company', 'positions', 'exams' => fn ($query) => $query->with('organization')->latest()])
                ->withCount('exams')
                ->orderByRaw('closed_at is not null')
                ->latest()
                ->paginate(15),
        ]);
    }
}
