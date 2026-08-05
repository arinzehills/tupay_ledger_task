<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function getBalance(): int
    {
        // Use single query with CASE statements instead of two separate queries
        // This fixes N+1 query problem and significantly improves performance
        // especially under high concurrency (e.g., during rapid swaps)
        $totals = $this->ledgerEntries()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as credits,
                COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as debits
            ")
            ->first();

        return (int) ($totals->credits - $totals->debits);
    }
}
