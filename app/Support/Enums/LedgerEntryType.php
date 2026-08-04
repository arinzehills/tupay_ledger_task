<?php

namespace App\Support\Enums;

enum LedgerEntryType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}