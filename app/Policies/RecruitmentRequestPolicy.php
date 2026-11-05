<?php

namespace App\Policies;

use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecruitmentRequestPolicy
{
    /**
     * Company staff see their own company's requests; LPK staff see every
     * request so they can follow it up.
     */
    public function view(User $user, RecruitmentRequest $request): bool
    {
        return $user->isLpk() || ($user->isCompany() && $user->organization_id === $request->company_id);
    }

    /**
     * The company may change the request until an LPK made a session from it.
     */
    public function update(User $user, RecruitmentRequest $request): Response
    {
        if (! $user->isCompany() || $user->organization_id !== $request->company_id) {
            return Response::denyAsNotFound();
        }

        return match (true) {
            $request->isClosed() => Response::deny('Permintaan ini sudah ditutup.'),
            $request->isProcessed() => Response::deny('LPK sudah membuat sesi tes dari permintaan ini, sehingga tidak dapat diubah lagi.'),
            default => Response::allow(),
        };
    }

    public function delete(User $user, RecruitmentRequest $request): Response
    {
        return $this->update($user, $request);
    }

    /**
     * Closing stops LPKs from making new sessions from the request.
     */
    public function close(User $user, RecruitmentRequest $request): Response
    {
        if (! $user->isCompany() || $user->organization_id !== $request->company_id) {
            return Response::denyAsNotFound();
        }

        return $request->isClosed() ? Response::deny('Permintaan ini sudah ditutup.') : Response::allow();
    }

    /**
     * LPK staff turn an open request into a test session.
     */
    public function followUp(User $user, RecruitmentRequest $request): Response
    {
        if (! $user->isLpk()) {
            return Response::denyAsNotFound();
        }

        return $request->isClosed() ? Response::deny('Permintaan ini sudah ditutup perusahaan.') : Response::allow();
    }
}
