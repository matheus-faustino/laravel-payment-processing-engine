<?php

namespace App\Services;

use App\Contracts\TransactionServiceInterface;
use App\Data\TransactionData;
use App\Enums\OutboxStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransactionException;
use App\Models\Account;
use App\Models\Outbox;
use App\Models\Transaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Override;
use Throwable;

class TransactionService implements TransactionServiceInterface
{
    #[Override]
    public function execute(TransactionData $transactionData, string $idempotencyKey): void
    {

        if ($transactionData->amount <= 0) {
            throw new InvalidTransactionException('The transaction amount cant be lower or equal to zero.', Response::HTTP_BAD_REQUEST);
        }

        if ($transactionData->sourceAccountId === $transactionData->destinationAccountId) {
            throw new InvalidTransactionException('The transaction cant be excuted between the same accounts', Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::transaction(function () use ($transactionData, $idempotencyKey) {

                $transaction = Transaction::create([
                    'source_account_id' => $transactionData->sourceAccountId,
                    'destination_account_id' => $transactionData->destinationAccountId,
                    'amount' => $transactionData->amount,
                    'type' => $transactionData->type,
                    'status' => TransactionStatus::PENDING,
                    'idempotency_key' => $idempotencyKey,
                ]);

                match ($transaction->type) {
                    TransactionType::DEPOSIT => $this->processDeposit($transaction),
                    TransactionType::WITHDRAW => $this->processWithdraw($transaction),
                    TransactionType::TRANSFER => $this->processTransfer($transaction),
                };

                $transaction->update(['status' => TransactionStatus::PROCESSED]);

                Outbox::create([
                    'event_type' => 'transaction.processed',
                    'payload' => [
                        'transaction' => $transaction->toArray(),
                    ],
                    'status' => OutboxStatus::PENDING,
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->markAsFailed($transactionData, $idempotencyKey, $e);

            throw $e;
        }
    }

    #[Override]
    public function processDeposit(Transaction $transaction): void
    {
        $account = Account::where('id', $transaction->destination_account_id)->lockForUpdate()->first();
        $account->increment('balance', $transaction->amount);
    }

    #[Override]
    public function processWithdraw(Transaction $transaction): void
    {
        $sourceAccount = Account::where('id', $transaction->source_account_id)->lockForUpdate()->firstOrFail();

        if ($sourceAccount->balance < $transaction->amount) {
            throw new InsufficientBalanceException;
        }

        $sourceAccount->decrement('balance', $transaction->amount);
    }

    #[Override]
    public function processTransfer(Transaction $transaction): void
    {
        [$firstAccountId, $secondAccountId] = array_values(Arr::sort([$transaction->source_account_id, $transaction->destination_account_id]));

        $firstAccount = Account::where('id', $firstAccountId)->lockForUpdate()->firstOrFail();
        $secondAccount = Account::where('id', $secondAccountId)->lockForUpdate()->firstOrFail();

        $sourceAccount = $firstAccount->id === $transaction->source_account_id ? $firstAccount : $secondAccount;
        $destinationAccount = $firstAccount->id === $transaction->destination_account_id ? $firstAccount : $secondAccount;

        if ($sourceAccount->balance < $transaction->amount) {
            throw new InsufficientBalanceException;
        }

        $sourceAccount->decrement('balance', $transaction->amount);
        $destinationAccount->increment('balance', $transaction->amount);
    }

    #[Override]
    public function markAsFailed(TransactionData $transactionData, string $idempotencyKey, Throwable $e): void
    {
        DB::transaction(function () use ($transactionData, $idempotencyKey, $e) {
            $transaction = Transaction::create([
                'source_account_id' => $transactionData->sourceAccountId,
                'destination_account_id' => $transactionData->destinationAccountId,
                'amount' => $transactionData->amount,
                'type' => $transactionData->type,
                'status' => TransactionStatus::FAILED,
                'idempotency_key' => $idempotencyKey,
            ]);

            Outbox::create([
                'event_type' => 'transaction.failed',
                'payload' => [
                    'transaction' => $transaction->toArray(),
                    'error' => $e->getMessage(),
                ],
                'status' => OutboxStatus::PENDING,
            ]);
        });
    }
}
