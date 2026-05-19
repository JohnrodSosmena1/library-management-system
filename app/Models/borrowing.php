<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Borrowing Model
 * 
 * ═══════════════════════════════════════════════════════════════
 * DATABASE TRANSACTION & TRIGGER STRATEGY
 * ═══════════════════════════════════════════════════════════════
 * 
 * TRIGGERS (INTENTIONALLY DISABLED):
 * - Triggers exist in migrations but are NOT used by application
 * - Kept in database for visibility/audit purposes only
 * - See: 2026_05_09_000001_create_library_triggers_if_missing.php
 * - See: 2026_05_10_000001_disable_quantity_triggers.php
 * 
 * WHY?
 * - Avoids double-increment/decrement from concurrent transactions
 * - Gives full control of inventory logic to application code
 * - Simpler debugging and transaction management
 * 
 * TRANSACTION SAFETY:
 * ✅ All inventory modifications wrapped in DB::transaction()
 * ✅ Uses pessimistic locking (lockForUpdate) on critical sections
 * ✅ Prevents race conditions during concurrent approvals/returns
 * 
 * KEY OPERATIONS:
 * 1. approveBorrowRequest()  → Decrement quantity
 * 2. processReturn()         → Increment quantity
 * 3. rejectBorrowRequest()   → No inventory change
 * 4. markOverdue()           → Status update only
 * 
 * ═══════════════════════════════════════════════════════════════
 */
class Borrowing extends Model
{
    // Status Constants
    const STATUS_PENDING = 'Pending';
    const STATUS_APPROVED = 'Approved';
    const STATUS_BORROWED = 'Borrowed';
    const STATUS_OVERDUE = 'Overdue';
    const STATUS_RETURN_REQUESTED = 'Return Requested';
    const STATUS_RETURNED = 'Returned';
    const STATUS_REJECTED = 'Rejected';

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
        if (in_array($this->status, ['Returned', 'Return Requested']) && $this->return_date) {
            $diff = $this->return_date->diffInDays($this->due_date, false);
            return $diff < 0 ? (int) abs($diff) : 0;
        }

        if ($this->due_date && $this->due_date->isPast()) {
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
        return $query->whereIn('status', ['Borrowed', 'Overdue', 'Return Requested']);
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