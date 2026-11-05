<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\View\View;

/**
 * Recruitment requests released by companies, for the LPK to follow up
 * with a test session.
 */
class RecruitmentRequestController extends Controller
{
    public function index(): View
    {
        return view('lpk.recruitments.index', [
            'requests' => RecruitmentRequest::query()
                ->with(['company', 'positions', 'exams' => fn ($query) => $query->latest()])
                ->withCount('exams')
                ->orderByRaw('closed_at is not null')
                ->latest()
                ->paginate(15),
        ]);
    }
}
