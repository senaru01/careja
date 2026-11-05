<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamPolicy
{
    /**
     * LPK staff manage the sessions of their own LPK only.
     */
    public function view(User $user, Exam $exam): bool
    {
        return $user->isLpk() && $user->organization_id === $exam->organization_id;
    }

    public function update(User $user, Exam $exam): Response
    {
        if (! $this->view($user, $exam)) {
            return Response::deny();
        }

        return $exam->isLocked()
            ? Response::deny('Sesi tes sudah diterbitkan sehingga isi dan soalnya tidak dapat diubah lagi. Jadwal masih bisa dimundurkan.')
            : Response::allow();
    }

    public function delete(User $user, Exam $exam): Response
    {
        $response = $this->update($user, $exam);

        if ($response->allowed() && $exam->pendingPublicationPayment()) {
            return Response::deny('Ada pembayaran penerbitan yang sedang berjalan untuk sesi ini.');
        }

        return $response;
    }

    /**
     * The LPK pays the session fee to publish it.
     */
    public function publish(User $user, Exam $exam): Response
    {
        if (! $this->view($user, $exam)) {
            return Response::deny();
        }

        return match (true) {
            $exam->isPublished() => Response::deny('Sesi tes ini sudah terbit.'),
            ! $exam->questions()->exists() => Response::deny('Tambahkan soal terlebih dahulu sebelum menerbitkan sesi tes.'),
            ! $exam->company_id || ! $exam->starts_at || ! $exam->quota => Response::deny('Lengkapi perusahaan, kuota, dan jadwal sesi tes terlebih dahulu.'),
            $exam->starts_at->lessThan(now()->addHour()) => Response::deny('Jadwal tes minimal 1 jam dari sekarang agar pencari kerja sempat mendaftar. Ubah jadwal terlebih dahulu.'),
            default => Response::allow(),
        };
    }

    /**
     * The schedule can only be pushed back, and only before the test starts.
     */
    public function reschedule(User $user, Exam $exam): Response
    {
        if (! $this->view($user, $exam)) {
            return Response::deny();
        }

        return $exam->isOpenForRegistration()
            ? Response::allow()
            : Response::deny('Jadwal hanya bisa dimundurkan untuk sesi yang sudah terbit dan belum dimulai.');
    }

    /**
     * Everyone takes the test together in one room: the LPK starts it once
     * registration is closed (the scheduled start time has passed).
     */
    public function start(User $user, Exam $exam): Response
    {
        if (! $this->view($user, $exam)) {
            return Response::deny();
        }

        return match (true) {
            $exam->isOpenForRegistration() => Response::deny('Tes baru bisa dimulai pada jadwal: '.$exam->starts_at->translatedFormat('d M Y H:i').' WIB.'),
            ! $exam->isWaitingForStart() => Response::deny('Tes ini sudah dimulai atau sudah selesai.'),
            default => Response::allow(),
        };
    }

    /**
     * The live score board is open to the LPK running the session and the
     * company it recruits for.
     */
    public function monitor(User $user, Exam $exam): bool
    {
        return $this->view($user, $exam) || $this->follow($user, $exam);
    }

    /**
     * Company staff follow the sessions run for their company.
     */
    public function follow(User $user, Exam $exam): bool
    {
        return $user->isCompany() && $exam->isPublished() && $user->organization_id === $exam->company_id;
    }

    /**
     * Picking candidates for interview starts once every participant had the
     * chance to finish, so the ranking is final.
     */
    public function select(User $user, Exam $exam): Response
    {
        if (! $this->follow($user, $exam)) {
            return Response::deny();
        }

        return match (true) {
            ! $exam->hasEnded() => Response::deny('Pemilihan kandidat dibuka setelah sesi tes selesai dan peringkat final.'),
            ! $exam->hasPaidInterviewAccess() => Response::deny('Bayar biaya pengambilan kandidat wawancara terlebih dahulu.'),
            default => Response::allow(),
        };
    }

    /**
     * The company pays the interview fee once per finished session.
     */
    public function payInterviewAccess(User $user, Exam $exam): Response
    {
        if (! $this->follow($user, $exam)) {
            return Response::deny();
        }

        return match (true) {
            ! $exam->hasEnded() => Response::deny('Biaya pengambilan kandidat dibayar setelah sesi tes selesai.'),
            $exam->hasPaidInterviewAccess() => Response::deny('Biaya pengambilan kandidat untuk sesi ini sudah dibayar.'),
            default => Response::allow(),
        };
    }

    /**
     * Job seekers can register while registration is open.
     */
    public function register(User $user, Exam $exam): Response
    {
        if (! $user->isWorker() || ! $exam->isPublished()) {
            return Response::denyAsNotFound();
        }

        $attempt = $exam->attempts()->where('user_id', $user->id)->first();

        return match (true) {
            ! $exam->isOpenForRegistration() => Response::deny('Pendaftaran sesi ini sudah ditutup.'),
            ! $user->hasActiveMembership() => Response::deny('Aktifkan keanggotaan Anda terlebih dahulu untuk ikut sesi tes.'),
            ! $user->hasCompleteCandidateProfile() => Response::deny('Lengkapi profil dan CV terlebih dahulu.'),
            $attempt?->isWithdrawn() => Response::deny('Anda sudah keluar dari sesi ini. Gunakan token Anda untuk sesi lain.'),
            $attempt?->isRegistered() => Response::deny('Anda sudah terdaftar di sesi ini.'),
            default => Response::allow(),
        };
    }
}
