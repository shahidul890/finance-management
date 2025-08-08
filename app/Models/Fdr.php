<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Fdr extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_id',
        'fdr_name',
        'fdr_number',
        'principal_amount',
        'interest_rate',
        'tenure_months',
        'start_date',
        'maturity_date',
        'maturity_amount',
        'interest_payout',
        'interest_earned',
        'status',
        'auto_renewal',
        'last_interest_paid',
        'additional_info',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'maturity_amount' => 'decimal:2',
        'interest_earned' => 'decimal:2',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'last_interest_paid' => 'date',
        'auto_renewal' => 'boolean',
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
    public function getDaysToMaturityAttribute(): int
    {
        return Carbon::now()->diffInDays($this->maturity_date, false);
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalDays = Carbon::parse($this->start_date)->diffInDays($this->maturity_date);
        $daysPassed = Carbon::parse($this->start_date)->diffInDays(Carbon::now());
        
        return $totalDays > 0 ? min(($daysPassed / $totalDays) * 100, 100) : 0;
    }
}
