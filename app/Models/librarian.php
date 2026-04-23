<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Librarian extends Authenticatable
{
    protected $fillable = [
        'name',
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

    public function getFormattedIdAttribute(): string
    {
        return 'LIB-' . str_pad($this->id, 2, '0', STR_PAD_LEFT);
    }
}