<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'category_id',
        'isbn',
        'quantity',
        'status',
        'description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function activeBorrowing(): ?Borrowing
    {
        return $this->borrowings()
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->latest()
            ->first();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'Available';
    }

    // Auto-generate book ID prefix for display
    public function getFormattedIdAttribute(): string
    {
        return 'BK-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }
}