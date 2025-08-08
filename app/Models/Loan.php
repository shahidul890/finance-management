<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Loan extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_id',
        'loan_name',
        'loan_number',
        'loan_type',
        'principal_amount',
        'interest_rate',
        'tenure_months',
        'monthly_emi',
        'start_date',
        'end_date',
        'total_amount_payable',
        'amount_paid',
        'outstanding_balance',
        'paid_emis',
        'remaining_emis',
        'status',
        'last_payment_date',
        'next_payment_date',
        'auto_debit',
        'penalty_amount',
        'additional_info',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_emi' => 'decimal:2',
        'total_amount_payable' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('loan_type', $type);
    }

    // Accessors
    public function getProgressPercentageAttribute(): float
    {
        return $this->tenure_months > 0 ? ($this->paid_emis / $this->tenure_months) * 100 : 0;
    }

    public function getDaysToNextPaymentAttribute(): int
    {
        return Carbon::now()->diffInDays($this->next_payment_date, false);
    }
}
