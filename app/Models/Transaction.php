<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    protected $fillable = [
        'bank_account_id',
        'type',
        'amount',
        'description',
        'related_model',
        'related_model_id',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Get the bank account associated with the transaction.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the related model associated with the transaction.
     */
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(
            $this->related_model,
            'related_model_id'
        );
    }

    /**
     * Scope a query to only include transactions of a given type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include income transactions.
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'in');
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'out');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by bank account.
     */
    public function scopeForBankAccount(Builder $query, int $bankAccountId): Builder
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    /**
     * Get formatted amount with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get transaction type label.
     */
    public function getTypeLabel(): string
    {
        return $this->type === 'in' ? 'Income' : 'Expense';
    }

    /**
     * Check if transaction is income.
     */
    public function isIncome(): bool
    {
        return $this->type === 'in';
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->type === 'out';
    }
}
