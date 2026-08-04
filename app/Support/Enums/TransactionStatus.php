<?php

namespace App\Support\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
}