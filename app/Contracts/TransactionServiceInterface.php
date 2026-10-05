<?php

namespace App\Contracts;

use App\Data\TransactionData;

interface TransactionServiceInterface
{
    public function execute(TransactionData $transactionData, string $idempotencyKey): void;
}
