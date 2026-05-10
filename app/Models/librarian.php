<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Librarian extends Authenticatable
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'contact_no',
        'role',
    ];

    protected $hidden = ['password'];

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
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

    public function getFormattedIdAttribute(): string
    {
        return 'LIB-' . str_pad($this->id, 2, '0', STR_PAD_LEFT);
    }
}

