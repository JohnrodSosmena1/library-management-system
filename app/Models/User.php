<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'contact_no',
        'status',
        'password',
    ];

    protected $hidden = ['password'];

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function activeBorrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class)
            ->whereIn('status', ['Borrowed', 'Overdue']);
    }

    public function hasOverdue(): bool
    {
        return $this->borrowings()
            ->where('status', 'Overdue')
            ->exists();
    }

    public function activeBorrowCount(): int
    {
        return $this->activeBorrowings()->count();
    }

    public function fullName(): string
    {
        $first = $this->first_name ?? '';
        $last = $this->last_name ?? '';

        return trim($first . ' ' . $last);
    }

    public function getFullNameAttribute(): string
    {
        return $this->fullName();
    }
}

