<?php

namespace App\Services;

use App\Contracts\TransactionServiceInterface;
use App\Data\TransactionData;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Override;
use Throwable;

class TransactionService implements TransactionServiceInterface
{
    #[Override]
    public function execute(TransactionData $transactionData, string $idempotencyKey): void
    {
        $transaction = Transaction::create([
            'source_account_id' => $transactionData->sourceAccountId,
            'destination_account_id' => $transactionData->destinationAccountId,
            'amount' => $transactionData->amount,
            'type' => $transactionData->type,
            'status' => TransactionStatus::PENDING,
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            DB::transaction(function () use ($transaction, $transactionData) {

                if ($transactionData->type === TransactionType::DEPOSIT) {
                    $destinationAccount = Account::where('id', $transactionData->destinationAccountId)->lockForUpdate()->firstOrFail();
                    $destinationAccount->increment('balance', $transactionData->amount);

                    $transaction->update(['status' => TransactionStatus::PROCESSED]);

                    return;
                }

                if ($transactionData->type === TransactionType::WITHDRAW) {
                    $sourceAccount = Account::where('id', $transactionData->sourceAccountId)->lockForUpdate()->firstOrFail();

                    if ($sourceAccount->balance < $transactionData->amount) {
                        throw new InsufficientBalanceException;
                    }

                    $sourceAccount->decrement('balance', $transactionData->amount);

                    $transaction->update(['status' => TransactionStatus::PROCESSED]);

                    return;
                }

                [$firstAccountId, $secondAccountId] = array_values(Arr::sort([$transactionData->sourceAccountId, $transactionData->destinationAccountId]));

                $firstAccount = Account::where('id', $firstAccountId)->lockForUpdate()->firstOrFail();
                $secondAccount = Account::where('id', $secondAccountId)->lockForUpdate()->firstOrFail();

                $sourceAccount = $firstAccount->id === $transactionData->sourceAccountId ? $firstAccount : $secondAccount;
                $destinationAccount = $firstAccount->id === $transactionData->destinationAccountId ? $firstAccount : $secondAccount;

                if ($sourceAccount->balance < $transactionData->amount) {
                    throw new InsufficientBalanceException;
                }

                $sourceAccount->decrement('balance', $transactionData->amount);
                $destinationAccount->increment('balance', $transactionData->amount);

                $transaction->update(['status' => TransactionStatus::PROCESSED]);
            });
        } catch (Throwable $e) {
            $transaction->update(['status' => TransactionStatus::FAILED]);

            throw $e;
        }
    }
}
