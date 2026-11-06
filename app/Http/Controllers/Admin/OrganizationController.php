<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationType;
use App\Enums\PaymentCollector;
use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    /**
     * Every LPK and company, with its address, connections and (for an LPK)
     * the membership money received and Careja's share of it.
     */
    public function index(): View
    {
        $organizations = Organization::query()
            ->with(['staff', 'connectedLpks', 'connectedCompanies'])
            ->withCount('members')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'membershipRevenue' => Payment::paid()->membership()
                ->whereNotNull('lpk_id')
                ->groupBy('lpk_id')
                ->selectRaw('lpk_id, sum(amount) as total')
                ->pluck('total', 'lpk_id'),
        ]);
    }

    public function create(): View
    {
        return view('admin.organizations.create', [
            'types' => OrganizationType::cases(),
        ]);
    }

    /**
     * Create an LPK or company together with its first login account.
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = DB::transaction(function () use ($request) {
            $organization = Organization::create($request->safe()->only(['type', 'name', 'subdomain', 'email', 'phone', 'address', 'description']));

            $account = new User([
                'name' => $request->validated('account_name'),
                'email' => $request->validated('account_email'),
                'password' => $request->validated('account_password'),
            ]);

            $account->forceFill([
                'role' => $organization->type->userRole(),
                'organization_id' => $organization->id,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'email_verified_at' => now(),
            ])->save();

            return $organization;
        });

        return redirect()->route('admin.organizations.edit', $organization)
            ->with('status', "{$organization->type->label()} {$organization->name} beserta akun loginnya berhasil dibuat. "
                .($organization->isLpk() ? 'Atur biaya dan pembayarannya di bawah.' : 'Hubungkan dengan LPK di bawah.'));
    }

    public function edit(Organization $organization): View
    {
        return view('admin.organizations.edit', [
            'organization' => $organization->load('connectedLpks'),
            'lpks' => Organization::lpks()->orderBy('name')->get(),
            'collectors' => PaymentCollector::cases(),
            'feeFields' => [
                'membership_fee' => PaymentPurpose::Membership,
                'exam_session_fee' => PaymentPurpose::ExamSession,
                'exam_registration_fee' => PaymentPurpose::ExamRegistration,
                'interview_fee' => PaymentPurpose::InterviewAccess,
                'medical_checkup_fee' => PaymentPurpose::MedicalCheckup,
            ],
        ]);
    }

    /**
     * Save the settings. For a company, the ticked LPKs become its connections.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        DB::transaction(function () use ($request, $organization) {
            $organization->update($request->safe()->except('lpk_ids'));

            if ($organization->isCompany()) {
                $organization->connectedLpks()->sync($request->validated('lpk_ids') ?? []);
            }
        });

        return redirect()->route('admin.organizations.index')
            ->with('status', "Pengaturan {$organization->name} disimpan.");
    }
}
