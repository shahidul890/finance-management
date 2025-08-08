<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class BankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_name',
        'account_number',
        'account_type',
        'initial_amount',
        'current_balance',
        'available_balance',
        'branch',
        'ifsc_code',
        'swift_code',
        'is_active',
        'additional_info',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'additional_info' => 'array',
    ];

    protected $appends = [
        'possible_current_balance',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dps(): HasMany
    {
        return $this->hasMany(Dps::class);
    }

    public function fdrs(): HasMany
    {
        return $this->hasMany(Fdr::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->current_balance, 2);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getPossibleCurrentBalanceAttribute()
    {
        return $this->incomes()->sum('amount') - $this->expenses()->sum('amount');
    }

}
