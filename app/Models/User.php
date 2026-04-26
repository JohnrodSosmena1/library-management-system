<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
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
}