<?php

namespace App\Contracts;

use App\Data\TransactionData;
use App\Models\Transaction;
use Throwable;

interface TransactionServiceInterface
{
    public function execute(TransactionData $transactionData, string $idempotencyKey): void;

    public function processDeposit(Transaction $transaction): void;

    public function processWithdraw(Transaction $transaction): void;

    public function processTransfer(Transaction $transaction): void;

    public function markAsFailed(TransactionData $transactionData, string $idempotencyKey, Throwable $e): void;
}
