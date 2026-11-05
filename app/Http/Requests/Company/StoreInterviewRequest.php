<?php

namespace App\Http\Requests\Company;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamPosition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Invite candidates from a finished test session's ranking (of one position
 * when the session has a position table). Only passed participants can be
 * picked, never more than the open seats, and passing over a better ranked
 * candidate requires a written (public) reason.
 */
class StoreInterviewRequest extends FormRequest
{
    /**
     * @var Collection<int, ExamAttempt>|null
     */
    private ?Collection $ranking = null;

    public function authorize(): bool
    {
        return $this->user()->can('select', $this->exam());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_position_id' => [
                Rule::requiredIf(fn () => $this->exam()->positions()->exists()),
                'nullable',
                'integer',
                Rule::exists(ExamPosition::class, 'id')->where('exam_id', $this->exam()->id),
            ],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'skip_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $exam = $this->exam();
                $position = $this->position();
                $selected = collect($this->input('user_ids'))->map(fn ($id) => (int) $id);
                $ranking = $this->ranking();
                $invited = $exam->interviews()->pluck('user_id');
                $openSeats = $exam->openSeats($position);

                if ($selected->diff($ranking->pluck('user_id'))->isNotEmpty()) {
                    $validator->errors()->add('user_ids', $position
                        ? 'Hanya peserta yang lulus tes untuk posisi '.$position->position.' yang dapat diundang.'
                        : 'Hanya peserta yang lulus tes sesi ini yang dapat diundang.');

                    return;
                }

                if ($selected->intersect($invited)->isNotEmpty()) {
                    $validator->errors()->add('user_ids', 'Sebagian kandidat sudah pernah diundang dari sesi ini.');

                    return;
                }

                if ($selected->count() > $openSeats) {
                    $validator->errors()->add('user_ids', "Sisa kuota hanya {$openSeats} orang.");

                    return;
                }

                if ($this->skippedRanks()->isNotEmpty() && mb_strlen(trim((string) $this->input('skip_reason'))) < 10) {
                    $validator->errors()->add('skip_reason', 'Anda melewati peringkat '.$this->skippedRanks()->implode(', ')
                        .'. Tulis alasannya (minimal 10 karakter); alasan ini terlihat oleh LPK dan peserta.');
                }
            },
        ];
    }

    public function exam(): Exam
    {
        return $this->route('exam');
    }

    /**
     * The position the candidates are picked for, if the session has positions.
     */
    public function position(): ?ExamPosition
    {
        $id = $this->input('exam_position_id');

        return $id ? $this->exam()->positions()->find($id) : null;
    }

    /**
     * @return Collection<int, ExamAttempt>
     */
    public function ranking(): Collection
    {
        return $this->ranking ??= $this->exam()->ranking($this->position());
    }

    /**
     * Ranks of passed candidates that are better than someone picked now but
     * were neither picked now nor invited before.
     *
     * @return Collection<int, int>
     */
    public function skippedRanks(): Collection
    {
        $selected = collect($this->input('user_ids'))->map(fn ($id) => (int) $id);
        $invited = $this->exam()->interviews()->pluck('user_id');
        $worstPicked = $this->ranking()->whereIn('user_id', $selected)->max('rank') ?? 0;

        return $this->ranking()
            ->filter(fn (ExamAttempt $attempt) => $attempt->rank < $worstPicked
                && ! $selected->contains($attempt->user_id)
                && ! $invited->contains($attempt->user_id))
            ->pluck('rank')
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'exam_position_id' => 'posisi',
            'user_ids' => 'kandidat',
            'scheduled_at' => 'jadwal',
            'location' => 'tempat / link',
            'notes' => 'catatan',
            'skip_reason' => 'alasan melewati peringkat',
        ];
    }
}
