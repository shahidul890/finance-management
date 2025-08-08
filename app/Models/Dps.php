<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Dps extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_id',
        'dps_name',
        'dps_number',
        'monthly_installment',
        'tenure_months',
        'interest_rate',
        'start_date',
        'maturity_date',
        'total_deposited',
        'maturity_amount',
        'paid_installments',
        'remaining_installments',
        'status',
        'last_payment_date',
        'next_payment_date',
        'auto_debit',
        'additional_info',
    ];

    protected $casts = [
        'monthly_installment' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'maturity_amount' => 'decimal:2',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'last_payment_date' => 'date',
        'next_payment_date' => 'date',
        'auto_debit' => 'boolean',
        'additional_info' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // Accessors
    public function getProgressPercentageAttribute(): float
    {
        return $this->tenure_months > 0 ? ($this->paid_installments / $this->tenure_months) * 100 : 0;
    }

    public function getDaysToMaturityAttribute(): int
    {
        return Carbon::now()->diffInDays($this->maturity_date, false);
    }
}
