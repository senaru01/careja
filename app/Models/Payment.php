<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['amount', 'proof_path'])]
#[Hidden(['qr_string'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'purpose' => PaymentPurpose::class,
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'payment_due_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    /**
     * Scope a query to verified payments whose membership period is still running.
     *
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('purpose', PaymentPurpose::Membership)
            ->where('is_demo', false)
            ->where('status', PaymentStatus::Verified)
            ->where('expires_at', '>', now());
    }

    /**
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function membership(Builder $query): void
    {
        $query->where('purpose', PaymentPurpose::Membership);
    }

    public function isActive(): bool
    {
        return $this->status === PaymentStatus::Verified && $this->expires_at?->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isMidtrans(): bool
    {
        return $this->method === PaymentMethod::Midtrans;
    }

    public function isMembership(): bool
    {
        return ($this->purpose ?? PaymentPurpose::Membership) === PaymentPurpose::Membership;
    }

    /**
     * The test session this payment publishes or registers for.
     *
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Scope a query to verified payments made through the real flow (not the demo page).
     *
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function paid(Builder $query): void
    {
        $query->where('status', PaymentStatus::Verified)->where('is_demo', false);
    }

    /**
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', PaymentStatus::Pending)->where('is_demo', false);
    }

    /**
     * The interview a medical checkup payment is for.
     *
     * @return BelongsTo<Interview, $this>
     */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Record the admin's decision on a manual payment.
     */
    public function markAs(PaymentStatus $status, User $admin, ?string $note = null): void
    {
        if ($status === PaymentStatus::Verified) {
            $this->activate($admin);

            return;
        }

        $this->forceFill([
            'status' => $status,
            'note' => $note,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'expires_at' => null,
        ])->save();
    }

    /**
     * Mark the payment as paid. A membership payment activates the account for
     * the configured period, starting now. $verifier is null when Midtrans confirmed it.
     */
    public function activate(?User $verifier = null): void
    {
        $this->forceFill([
            'status' => PaymentStatus::Verified,
            'note' => null,
            'verified_by' => $verifier?->id,
            'verified_at' => now(),
            'expires_at' => $this->isMembership() && ! $this->is_demo ? now()->addMonths(config('careja.membership_months')) : null,
        ])->save();
    }

    /**
     * Apply a transaction status received from Midtrans. Only pending (or
     * locally expired) payments change, so repeated notifications are harmless.
     * A payment we already marked expired is still activated if Midtrans
     * reports it paid, so money received is never ignored.
     *
     * @param  array<string, mixed>  $transaction
     * @return bool true when the payment just became verified
     */
    public function applyGatewayStatus(array $transaction): bool
    {
        $gatewayStatus = (string) ($transaction['transaction_status'] ?? '');

        if (! $this->isPending() && $this->status !== PaymentStatus::Expired) {
            return false;
        }

        $this->forceFill(['gateway_status' => $gatewayStatus]);

        $paidInFull = (int) round((float) ($transaction['gross_amount'] ?? 0)) === $this->amount;
        $fraudAccepted = ($transaction['fraud_status'] ?? 'accept') === 'accept';

        if (in_array($gatewayStatus, ['settlement', 'capture'], true) && $paidInFull && $fraudAccepted) {
            $this->activate();

            return true;
        }

        if (! $this->isPending()) {
            $this->save();

            return false;
        }

        match ($gatewayStatus) {
            'expire' => $this->forceFill(['status' => PaymentStatus::Expired, 'note' => 'QRIS kedaluwarsa sebelum dibayar.']),
            'cancel', 'deny', 'failure' => $this->forceFill(['status' => PaymentStatus::Rejected, 'note' => "Transaksi Midtrans: {$gatewayStatus}."]),
            default => null,
        };

        $this->save();

        return false;
    }
}
