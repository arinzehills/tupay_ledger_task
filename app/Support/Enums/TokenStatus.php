<?php

namespace App\Support\Enums;

enum TokenStatus: string
{
    case VALID = 'valid';
    case CONSUMED = 'consumed';
    case EXPIRED = 'expired';
}
