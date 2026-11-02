<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        return view('admin.organizations.index', [
            'organizations' => Organization::with('users')->orderBy('type')->orderBy('name')->get(),
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
            $organization = Organization::create($request->safe()->only(['type', 'name', 'email', 'phone', 'address', 'description']));

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

        return redirect()->route('admin.organizations.index')
            ->with('status', "{$organization->type->label()} {$organization->name} beserta akun loginnya berhasil dibuat.");
    }
}
