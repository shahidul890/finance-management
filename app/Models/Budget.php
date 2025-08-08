<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'budget_name',
        'budget_amount',
        'spent_amount',
        'period_type', // monthly, yearly, custom
        'start_date',
        'end_date',
        'alert_percentage', // Send alert when spent percentage reaches this
        'status', // active, paused, completed
        'description',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'alert_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    public function scopeCurrent(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    // Accessors
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->budget_amount - $this->spent_amount);
    }

    public function getSpentPercentageAttribute(): float
    {
        if ($this->budget_amount == 0) return 0;
        return ($this->spent_amount / $this->budget_amount) * 100;
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return $this->spent_amount > $this->budget_amount;
    }

    public function getIsAlertTriggeredAttribute(): bool
    {
        return $this->spent_percentage >= $this->alert_percentage;
    }

    // Methods
    public function updateSpentAmount(): void
    {
        $spent = $this->category->expenses()
            ->whereBetween('expense_date', [$this->start_date, $this->end_date])
            ->sum('amount');
        
        $this->update(['spent_amount' => $spent]);
    }
}
