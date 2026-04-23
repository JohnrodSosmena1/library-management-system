<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    const PENALTY_RATE = 5.00; // PHP per day
    const BORROW_LIMIT = 3;
    const LOAN_DAYS    = 30;

    protected $fillable = [
        'user_id',
        'book_id',
        'librarian_id',
        'date_borrowed',
        'due_date',
        'return_date',
        'status',
        'penalty',
    ];

    protected $casts = [
        'date_borrowed' => 'date',
        'due_date'      => 'date',
        'return_date'   => 'date',
        'penalty'       => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function librarian(): BelongsTo
    {
        return $this->belongsTo(Librarian::class);
    }

    // ── Computed Attributes ──────────────────────────────────

    public function getDaysLateAttribute(): int
    {
        if ($this->status === 'Returned' && $this->return_date) {
            $diff = $this->return_date->diffInDays($this->due_date, false);
            return $diff < 0 ? (int) abs($diff) : 0;
        }

        if ($this->due_date->isPast()) {
            return (int) $this->due_date->diffInDays(now());
        }

        return 0;
    }

    public function getComputedPenaltyAttribute(): float
    {
        return $this->days_late * self::PENALTY_RATE;
    }

    public function getFormattedIdAttribute(): string
    {
        return 'TXN-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Borrowed', 'Overdue']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'Overdue');
    }

    // ── Helpers ──────────────────────────────────────────────

    public static function checkEligibility(int $userId): array
    {
        $user   = User::findOrFail($userId);
        $active = $user->activeBorrowings()->count();
        $hasOverdue = $user->hasOverdue();

        if ($hasOverdue) {
            return ['eligible' => false, 'message' => 'User has overdue book(s). Resolve before borrowing.'];
        }

        if ($active >= self::BORROW_LIMIT) {
            return ['eligible' => false, 'message' => "Borrowing limit reached ({$active}/" . self::BORROW_LIMIT . ")."];
        }

        return [
            'eligible' => true,
            'message'  => "Eligible — {$active}/" . self::BORROW_LIMIT . " books borrowed, no overdue.",
            'active'   => $active,
        ];
    }
}