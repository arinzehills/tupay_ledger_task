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
        $credits = $this->ledgerEntries()
            ->where('type', 'credit')
            ->sum('amount');

        $debits = $this->ledgerEntries()
            ->where('type', 'debit')
            ->sum('amount');

        return $credits - $debits;
    }
}
