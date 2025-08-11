<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'expense_date',
        'user_id',
        'category_id',
        'payment_method',
        'receipt_path',
        'tags',
        'expense_type', // regular, dps_payment, fdr_investment, loan_payment
        'related_id', // ID of related DPS, FDR, or Loan
        'related_type', // dps, fdr, loan
        'bank_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date:Y-m-d',
        'tags' => 'array',
    ];

    /**
     * Get the user that owns the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for the expense.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the bank account for the expense.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the related DPS if expense is a DPS payment.
     */
    public function dps(): BelongsTo
    {
        return $this->belongsTo(Dps::class, 'related_id');
    }

    /**
     * Get the related FDR if expense is an FDR investment.
     */
    public function fdr(): BelongsTo
    {
        return $this->belongsTo(Fdr::class, 'related_id');
    }

    /**
     * Get the related Loan if expense is a loan payment.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'related_id');
    }

    /**
     * Scope a query to only include expenses for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by expense type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('expense_type', $type);
    }

    /**
     * Scope a query to filter regular expenses only.
     */
    public function scopeRegular($query)
    {
        return $query->where('expense_type', 'regular');
    }

    /**
     * Get the related model based on expense type.
     */
    public function getRelatedModelAttribute()
    {
        if (!$this->related_id || !$this->related_type) {
            return null;
        }

        switch ($this->related_type) {
            case 'dps':
                return $this->dps;
            case 'fdr':
                return $this->fdr;
            case 'loan':
                return $this->loan;
            default:
                return null;
        }
    }

    /**
     * Get formatted amount with currency.
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => '$' . number_format($this->amount, 2),
        );
    }
}
