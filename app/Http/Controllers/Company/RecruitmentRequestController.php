<?php

namespace App\Http\Controllers\Company;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\RecruitmentRequestRequest;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Notifications\RecruitmentRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Recruitment requests of a company: the positions it needs, sent to the
 * connected LPKs it picks and followed up by one of them with a test session.
 */
class RecruitmentRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('company.recruitments.index', [
            'requests' => RecruitmentRequest::query()
                ->where('company_id', $request->user()->organization_id)
                ->with('positions')
                ->withCount('exams')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $connectedLpks = $request->user()->organization->connectedLpks()->get();

        return view('company.recruitments.form', [
            'recruitment' => new RecruitmentRequest,
            'connectedLpks' => $connectedLpks,
            // A new request goes to every connected LPK unless the company unticks some.
            'selectedLpkIds' => $connectedLpks->modelKeys(),
        ]);
    }

    public function store(RecruitmentRequestRequest $request): RedirectResponse
    {
        $recruitment = DB::transaction(function () use ($request) {
            $recruitment = new RecruitmentRequest($request->safe()->only(['title', 'description', 'needed_by']));
            $recruitment->forceFill([
                'company_id' => $request->user()->organization_id,
                'created_by' => $request->user()->id,
            ])->save();
            $recruitment->syncPositions($request->positions());
            $recruitment->lpks()->sync($request->lpkIds());

            return $recruitment;
        });

        Notification::send(
            User::where('role', UserRole::Lpk)->whereIn('organization_id', $request->lpkIds())->whereNotNull('approved_at')->get(),
            new RecruitmentRequested($recruitment),
        );

        return redirect()->route('company.recruitments.show', $recruitment)
            ->with('status', 'Permintaan rekrutmen dikirim. LPK akan menindaklanjuti dengan sesi tes.');
    }

    public function show(RecruitmentRequest $recruitment): View
    {
        Gate::authorize('view', $recruitment);

        $recruitment->load(['positions', 'creator', 'lpks', 'exams' => fn ($query) => $query->with('organization')->withCount('participants')->latest()]);

        return view('company.recruitments.show', [
            'recruitment' => $recruitment,
            'canEdit' => Gate::allows('update', $recruitment),
        ]);
    }

    public function edit(Request $request, RecruitmentRequest $recruitment): View
    {
        Gate::authorize('update', $recruitment);

        return view('company.recruitments.form', [
            'recruitment' => $recruitment->load('positions'),
            'connectedLpks' => $request->user()->organization->connectedLpks()->get(),
            'selectedLpkIds' => $recruitment->lpks()->pluck('organizations.id')->all(),
        ]);
    }

    public function update(RecruitmentRequestRequest $request, RecruitmentRequest $recruitment): RedirectResponse
    {
        Gate::authorize('update', $recruitment);

        DB::transaction(function () use ($request, $recruitment) {
            $recruitment->update($request->safe()->only(['title', 'description', 'needed_by']));
            $recruitment->syncPositions($request->positions());
            $recruitment->lpks()->sync($request->lpkIds());
        });

        return redirect()->route('company.recruitments.show', $recruitment)->with('status', 'Permintaan rekrutmen diperbarui.');
    }

    public function destroy(RecruitmentRequest $recruitment): RedirectResponse
    {
        Gate::authorize('delete', $recruitment);

        $recruitment->delete();

        return redirect()->route('company.recruitments.index')->with('status', 'Permintaan rekrutmen dihapus.');
    }

    /**
     * Stop the request: LPKs can no longer make new sessions from it.
     * Sessions already made keep running.
     */
    public function close(RecruitmentRequest $recruitment): RedirectResponse
    {
        Gate::authorize('close', $recruitment);

        $recruitment->forceFill(['closed_at' => now()])->save();

        return back()->with('status', 'Permintaan rekrutmen ditutup.');
    }
}
