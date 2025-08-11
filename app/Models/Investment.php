<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class Investment extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'schema_type',
        'schema_id',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investable(): MorphTo
    {
        return $this->morphTo('investable', 'schema_type', 'schema_id');
    }

    public function dps(): BelongsTo
    {
        return $this->belongsTo(Dps::class, 'schema_id')->where('type', 'dps');
    }

    public function fdr(): BelongsTo
    {
        return $this->belongsTo(Fdr::class, 'schema_id')->where('type', 'fdr');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'schema_id')->where('type', 'loan');
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('schema_type', $type);
    }

    // Accessors
    public function getInvestmentDetailsAttribute()
    {
        return $this->investable;
    }

    public function getTitleAttribute(): string
    {
        $details = $this->investment_details;
        return match($this->type) {
            'dps' => $details?->dps_name ?? 'DPS Investment',
            'fdr' => $details?->fdr_name ?? 'FDR Investment',
            'loan' => $details?->loan_name ?? 'Loan',
            default => 'Investment',
        };
    }

    public function getAmountAttribute(): float
    {
        $details = $this->investment_details;
        return match($this->type) {
            'dps' => $details?->monthly_installment ?? 0,
            'fdr' => $details?->principal_amount ?? 0,
            'loan' => $details?->principal_amount ?? 0,
            default => 0,
        };
    }

    public function getStatusAttribute(): string
    {
        $details = $this->investment_details;
        return $details?->status ?? 'unknown';
    }
}
