<?php

namespace App\Enums;

enum TransactionType: string
{
    case TRANSFER = 'transfer';
    case DEPOSIT = 'deposit';
    case WITHDRAW = 'withdraw';
}
